<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * php artisan migrate:fresh --seed
 * Order matters: suppliers -> products -> clients & accounts -> orders.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SupplierSeeder::class,
            ProductSeeder::class,
            ClientSeeder::class,
            OrderSeeder::class,
        ]);
    }
}
