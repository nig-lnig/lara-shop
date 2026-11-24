<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

/** Fictitious suppliers (codes Fr-000001 ... are generated automatically). */
class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            ['name' => 'Papeterie Atlas', 'city' => 'Casablanca'],
            ['name' => 'Bureau Pro Maroc', 'city' => 'Rabat'],
            ['name' => 'Fournitures du Sud', 'city' => 'Agadir'],
            ['name' => 'Diffusion Livres Al Andalus', 'city' => 'Fès'],
            ['name' => 'Safi Office Supply', 'city' => 'Safi'],
            ['name' => 'Encres & Papiers', 'city' => 'Marrakech'],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::create($supplier);
        }
    }
}
