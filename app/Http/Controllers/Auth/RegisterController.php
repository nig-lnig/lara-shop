<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;

/**
 * Sign up. Creates TWO records in one transaction:
 *  - a Client (business data from the PDF: name, address, type, discount)
 *  - a User   (login account: email + hashed password) linked to that client.
 */
class RegisterController extends Controller
{
    public function create()
    {
        return view('auth.register');
    }

    public function store(Request $request)
    {
        // Same address written in capitals or with spaces = same account.
        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email')))]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => User::emailRules(),
            'password' => ['required', 'confirmed', Password::defaults()], // policy: AppServiceProvider
            'street' => ['required', 'string', 'max:190'],
            'postal_code' => ['required', 'string', 'max:10', 'regex:/^[0-9A-Za-z \-]+$/'],
            'city' => ['required', 'string', 'max:100'],
        ]);

        $user = DB::transaction(function () use ($data) {
            $client = Client::create([
                'name' => $data['name'],
                'street' => $data['street'],
                'postal_code' => $data['postal_code'],
                'city' => $data['city'],
                'type' => Client::DEFAULT_TYPE, // admin can change it later
                'special_discount' => 0,        // admin can change it later
            ]);

            $user = new User([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'], // hashed automatically (cast "hashed")
            ]);
            $user->role = User::ROLE_CUSTOMER;
            $user->client_id = $client->id;
            $user->save();

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('catalog.index')
            ->with('success', "Bienvenue {$user->name} ! Votre compte client {$user->client->code} est créé.");
    }
}
