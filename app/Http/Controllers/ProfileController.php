<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Illuminate\Validation\Rules\Password;

/**
 * Profile & address of the signed-in customer.
 * The client type and special discount are shown but can only be changed by an admin.
 */
class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        return view('profile.edit', [
            'user' => $request->user(),
            'client' => $request->user()->client,
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email')))]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => User::emailRules($user->id),
            'street' => ['required', 'string', 'max:190'],
            'postal_code' => ['required', 'string', 'max:10', 'regex:/^[0-9A-Za-z \-]+$/'],
            'city' => ['required', 'string', 'max:100'],
        ]);

        DB::transaction(function () use ($user, $data) {
            $user->update(['name' => $data['name'], 'email' => $data['email']]);
            // Only the address fields: type and discount are NOT taken from the form.
            $user->client->update([
                'name' => $data['name'],
                'street' => $data['street'],
                'postal_code' => $data['postal_code'],
                'city' => $data['city'],
            ]);
        });

        return back()->with('success', 'Votre profil a été mis à jour.');
    }

    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'different:current_password', Password::defaults()],
        ]);

        $request->user()->update(['password' => $data['password']]); // hashed by the model cast

        return back()->with('success', 'Votre mot de passe a été modifié.');
    }
}
