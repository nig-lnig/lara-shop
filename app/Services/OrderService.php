<?php

namespace App\Services;

use App\Exceptions\CheckoutException;
use App\Models\Client;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Business operations on orders: placing an order and changing its status.
 */
class OrderService
{
    /**
     * Turns the cart into a confirmed order.
     *
     * Everything happens in ONE database transaction: if any step fails
     * (for example not enough stock), nothing is saved at all.
     *
     * @param  array<int, int>  $cartItems  [product_id => quantity]
     * @param  string  $checkoutToken  random token from the checkout form; the
     *                                 same token can only create one order
     *                                 (protects against double clicks).
     *
     * @throws CheckoutException
     */
    public function placeOrder(Client $client, array $cartItems, string $paymentTerms, string $checkoutToken): Order
    {
        // 1. Same form submitted twice? Return the order created the first time.
        $existing = Order::where('checkout_token', $checkoutToken)->where('client_id', $client->id)->first();
        if ($existing) {
            return $existing;
        }

        if ($cartItems === []) {
            throw new CheckoutException(['Votre panier est vide.']);
        }

        try {
            return DB::transaction(function () use ($client, $cartItems, $paymentTerms, $checkoutToken) {
                // 2. Read products again from the database (never trust the browser).
                //    lockForUpdate() makes other checkouts wait on MySQL.
                $products = Product::whereIn('id', array_keys($cartItems))->lockForUpdate()->get()->keyBy('id');

                $lines = [];
                $errors = [];
                foreach ($cartItems as $productId => $quantity) {
                    $product = $products->get($productId);

                    if (! $product || ! $product->isPurchasable()) {
                        $errors[] = 'Un produit de votre panier n’est plus disponible.';
                    } elseif ($quantity > $product->stock) {
                        $errors[] = "Stock insuffisant pour « {$product->designation} » (disponible : {$product->stock}).";
                    } else {
                        $lines[] = [
                            'product_id' => $product->id,
                            'quantity' => $quantity,
                            'unit_price' => $product->unit_price, // price frozen now
                        ];
                    }
                }

                if ($errors !== []) {
                    throw new CheckoutException($errors);
                }

                // 3. Server-side totals with the client's CURRENT discount.
                $discountRate = $client->fresh()->special_discount;
                $totals = PriceCalculator::totals($lines, $discountRate);

                // 4. Save the order (its number Com-xxxxxx is generated automatically).
                $order = Order::create([
                    'order_date' => now(),
                    'payment_terms' => $paymentTerms,
                    'client_id' => $client->id,
                    'status' => 'pending',
                    'discount_rate' => $discountRate,
                    'subtotal' => $totals['subtotal'],
                    'discount_amount' => $totals['discount_amount'],
                    'total' => $totals['total'],
                    'checkout_token' => $checkoutToken,
                ]);

                // 5. Save the lines and take the quantities out of stock.
                foreach ($totals['lines'] as $line) {
                    $order->lines()->create($line);

                    // "where stock >= quantity" = a final safety check done by the database itself.
                    $updated = Product::whereKey($line['product_id'])
                        ->where('stock', '>=', $line['quantity'])
                        ->decrement('stock', $line['quantity']);

                    if ($updated === 0) {
                        throw new CheckoutException(['Le stock a changé pendant la commande. Veuillez réessayer.']);
                    }
                }

                return $order;
            });
        } catch (UniqueConstraintViolationException) {
            // Two identical submits arrived at the same moment: the first one won.
            return Order::where('checkout_token', $checkoutToken)->firstOrFail();
        }
    }

    /**
     * Moves an order to a new status. Cancelling puts the products back in
     * stock, and this can only ever happen ONCE per order.
     */
    public function changeStatus(Order $order, string $newStatus): Order
    {
        if (! array_key_exists($newStatus, $order->nextStatuses())) {
            throw ValidationException::withMessages([
                'status' => "Impossible de passer de « {$order->statusLabel()} » à « ".(Order::STATUSES[$newStatus] ?? $newStatus).' ».',
            ]);
        }

        DB::transaction(function () use ($order, $newStatus) {
            $order->update(['status' => $newStatus]);

            if ($newStatus === 'cancelled') {
                // Marks the order as "stock restored" only if it was not marked yet.
                // update() returns the number of rows changed: 1 the first time, 0 after.
                $firstTime = Order::whereKey($order->id)
                    ->whereNull('stock_restored_at')
                    ->update(['stock_restored_at' => now()]);

                if ($firstTime === 1) {
                    foreach ($order->lines as $line) {
                        Product::whereKey($line->product_id)->increment('stock', $line->quantity);
                    }
                }
            }
        });

        return $order->refresh();
    }
}
