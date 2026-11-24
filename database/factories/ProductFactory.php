<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Product> */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'designation' => ucfirst(fake()->words(3, true)),
            'unit_price' => '10.00',
            'family' => 'PA',
            'supplier_id' => Supplier::factory(),
            'stock' => 50,
            'low_stock_threshold' => 5,
            'is_listed' => true,
        ];
    }
}
