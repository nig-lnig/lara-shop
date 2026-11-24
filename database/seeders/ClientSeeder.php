<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Demo accounts (password for all: "password"):
 *   admin@larashop.test   -> administrator
 *   client@larashop.test  -> customer with a 10 % special discount
 *   youssef@larashop.test -> customer without discount
 */
class ClientSeeder extends Seeder
{
    public function run(): void
    {
        $admin = new User(['name' => 'Administrateur', 'email' => 'admin@larashop.test', 'password' => 'password']);
        $admin->role = User::ROLE_ADMIN;
        $admin->save();

        // [name, street, postal code, city, type, discount, account email or null]
        $clients = [
            ['Khadija Benali', '12 rue Ibn Battouta', '46000', 'Safi', 'PA', 10, 'client@larashop.test'],
            ['Youssef El Idrissi', '5 avenue Hassan II', '40000', 'Marrakech', 'PA', 0, 'youssef@larashop.test'],
            ['Cabinet Dr Amrani', '27 boulevard Zerktouni', '20000', 'Casablanca', 'LI', 5, 'cabinet.amrani@larashop.test'],
            ['Association Iqraa', '3 rue des Écoles', '46000', 'Safi', 'AS', 20, 'iqraa@larashop.test'],
            ['Commune de Safi – Service achats', 'Place de l’Indépendance', '46000', 'Safi', 'PU', 15, null],
            ['Lycée Ibn Khaldoun', 'Route de Sidi Bouzid', '46010', 'Safi', 'PU', 12, null],
            ['Imprimerie Al Amal', '18 zone industrielle', '24000', 'El Jadida', 'DI', 8, null],
            ['Sara Tazi', '9 rue Allal Ben Abdellah', '10000', 'Rabat', 'PA', 0, 'sara@larashop.test'],
        ];

        foreach ($clients as [$name, $street, $postal, $city, $type, $discount, $email]) {
            $client = Client::create([
                'name' => $name, 'street' => $street, 'postal_code' => $postal, 'city' => $city,
                'type' => $type, 'special_discount' => $discount,
            ]);

            if ($email) {
                $user = new User(['name' => $name, 'email' => $email, 'password' => 'password']);
                $user->role = User::ROLE_CUSTOMER;
                $user->client_id = $client->id;
                $user->save();
            }
        }

        // A few favorites for the demo customer
        $demo = User::where('email', 'client@larashop.test')->first();
        $demo->favoriteProducts()->attach(Product::whereIn('code', ['Pr-000001', 'Pr-000012', 'Pr-000030'])->pluck('id'));

        User::where('email', 'youssef@larashop.test')->first()
            ->favoriteProducts()->attach(Product::whereIn('code', ['Pr-000017', 'Pr-000026'])->pluck('id'));
    }
}
