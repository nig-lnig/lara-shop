<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Order;
use App\Models\Product;
use App\Services\PriceCalculator;
use Illuminate\Database\Seeder;

/**
 * Past orders in every status. Totals are calculated with the same
 * PriceCalculator as the real checkout.
 *
 * Seeded stock levels are considered "after" these orders, so the seeder
 * does not change stock.
 */
class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $client = fn (string $code) => Client::where('code', $code)->firstOrFail();
        $product = fn (string $code) => Product::where('code', $code)->firstOrFail();

        // [client code, days ago, payment terms, status, [[product code, qty, old price?], ...]]
        $orders = [
            // Historical price demo: the A4 ream cost 45.00 when this order was placed (now 49.90).
            ['Cli-000001', 60, 'LIV', 'delivered', [['Pr-000001', 4, '45.00'], ['Pr-000017', 1], ['Pr-000019', 2]]],
            ['Cli-000001', 21, 'T30', 'shipped', [['Pr-000026', 1], ['Pr-000002', 5]]],
            ['Cli-000001', 2, 'LIV', 'pending', [['Pr-000010', 1], ['Pr-000023', 3]]],
            ['Cli-000002', 45, 'LIV', 'delivered', [['Pr-000014', 1], ['Pr-000009', 2]]],
            ['Cli-000002', 10, 'LIV', 'cancelled', [['Pr-000012', 1]]],
            ['Cli-000002', 1, 'T30', 'preparing', [['Pr-000030', 1], ['Pr-000004', 3]]],
            ['Cli-000003', 33, 'T30', 'delivered', [['Pr-000013', 3], ['Pr-000001', 10, '45.00'], ['Pr-000016', 4]]],
            ['Cli-000003', 6, 'T30', 'preparing', [['Pr-000011', 2], ['Pr-000010', 2]]],
            ['Cli-000004', 28, 'LIV', 'delivered', [['Pr-000018', 10], ['Pr-000024', 6], ['Pr-000025', 8]]],
            ['Cli-000004', 4, 'LIV', 'pending', [['Pr-000002', 30], ['Pr-000021', 30]]],
            ['Cli-000005', 50, 'T30', 'delivered', [['Pr-000001', 40, '45.00'], ['Pr-000009', 25], ['Pr-000017', 10]]],
            ['Cli-000005', 15, 'T30', 'shipped', [['Pr-000012', 5], ['Pr-000016', 10]]],
            ['Cli-000006', 38, 'T30', 'delivered', [['Pr-000027', 4], ['Pr-000028', 10], ['Pr-000026', 6]]],
            ['Cli-000006', 3, 'T30', 'pending', [['Pr-000020', 50], ['Pr-000018', 20]]],
            ['Cli-000007', 18, 'LIV', 'cancelled', [['Pr-000006', 10]]],
            ['Cli-000008', 7, 'LIV', 'delivered', [['Pr-000029', 1], ['Pr-000003', 2]]],
            ['Cli-000008', 0, 'LIV', 'pending', [['Pr-000019', 1], ['Pr-000022', 1]]],
        ];

        foreach ($orders as [$clientCode, $daysAgo, $terms, $status, $items]) {
            $orderClient = $client($clientCode);

            $lines = [];
            foreach ($items as $item) {
                $p = $product($item[0]);
                $lines[] = ['product_id' => $p->id, 'quantity' => $item[1], 'unit_price' => $item[2] ?? $p->unit_price];
            }

            $totals = PriceCalculator::totals($lines, $orderClient->special_discount);
            $date = now()->subDays($daysAgo)->setTime(9 + $daysAgo % 8, ($daysAgo * 7) % 60);

            $order = Order::create([
                'order_date' => $date,
                'payment_terms' => $terms,
                'client_id' => $orderClient->id,
                'status' => $status,
                'discount_rate' => $orderClient->special_discount,
                'subtotal' => $totals['subtotal'],
                'discount_amount' => $totals['discount_amount'],
                'total' => $totals['total'],
            ]);
            $order->forceFill([
                'created_at' => $date,
                'updated_at' => $date,
                'stock_restored_at' => $status === 'cancelled' ? $date->copy()->addHours(3) : null,
            ])->saveQuietly();

            foreach ($totals['lines'] as $line) {
                $order->lines()->create($line);
            }
        }
    }
}
