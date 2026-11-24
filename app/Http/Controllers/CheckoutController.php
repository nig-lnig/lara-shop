<?php

namespace App\Http\Controllers;

use App\Exceptions\CheckoutException;
use App\Models\Order;
use App\Services\Cart;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/** Checkout: review the cart, choose the payment terms (LIV / T30), confirm. */
class CheckoutController extends Controller
{
    public function create(Cart $cart)
    {
        if ($cart->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Votre panier est vide.');
        }

        return view('checkout.create', [
            'cart' => $cart->summary(),
            'client' => auth()->user()->client,
            // A new random token for this form: the same token cannot create two orders.
            'checkoutToken' => Str::uuid()->toString(),
        ]);
    }

    public function store(Request $request, Cart $cart, OrderService $orders)
    {
        $data = $request->validate([
            'payment_terms' => ['required', Rule::in(array_keys(Order::PAYMENT_TERMS))],
            'checkout_token' => ['required', 'uuid'],
        ], [
            'payment_terms.required' => 'Choisissez une condition de paiement.',
        ]);

        try {
            $order = $orders->placeOrder(
                $request->user()->client,
                $cart->items(),
                $data['payment_terms'],
                $data['checkout_token'],
            );
        } catch (CheckoutException $e) {
            return redirect()->route('cart.index')->with('error', $e->getMessage());
        }

        $cart->clear();

        // The order page shows a big confirmation box when "just_ordered" is in the session.
        return redirect()->route('orders.show', $order)->with('just_ordered', true);
    }
}
