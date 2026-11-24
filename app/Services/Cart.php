<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Auth;

/**
 * The shopping cart, stored in the session as [product_id => quantity].
 *
 * Only ids and quantities are stored: prices are ALWAYS read again from the
 * database, so a customer can never change a price from the browser.
 */
class Cart
{
    private const KEY = 'cart';

    /** @return array<int, int> */
    public function items(): array
    {
        return session(self::KEY, []);
    }

    public function count(): int
    {
        return array_sum($this->items());
    }

    public function isEmpty(): bool
    {
        return $this->items() === [];
    }

    public function quantityOf(int $productId): int
    {
        return $this->items()[$productId] ?? 0;
    }

    public function set(int $productId, int $quantity): void
    {
        $items = $this->items();

        if ($quantity <= 0) {
            unset($items[$productId]);
        } else {
            $items[$productId] = $quantity;
        }

        session([self::KEY => $items]);
    }

    public function remove(int $productId): void
    {
        $this->set($productId, 0);
    }

    public function clear(): void
    {
        session()->forget(self::KEY);
    }

    /**
     * Cart content with current prices and totals, ready for the views.
     * Products that were archived since they were added are dropped.
     */
    public function summary(): array
    {
        $items = $this->items();
        $products = Product::available()->whereIn('id', array_keys($items))->get()->keyBy('id');

        $lines = [];
        foreach ($items as $productId => $quantity) {
            if ($product = $products->get($productId)) {
                $lines[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'unit_price' => $product->unit_price,
                    'enough_stock' => $quantity <= $product->stock,
                ];
            }
        }

        $discountRate = Auth::user()?->client?->special_discount ?? 0;
        $totals = PriceCalculator::totals($lines, $discountRate);

        return [
            'lines' => $totals['lines'],
            'discount_rate' => $discountRate,
            'subtotal' => $totals['subtotal'],
            'discount_amount' => $totals['discount_amount'],
            'total' => $totals['total'],
            'has_stock_problem' => collect($lines)->contains('enough_stock', false),
        ];
    }
}
