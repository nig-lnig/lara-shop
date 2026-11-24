<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\Cart;
use Illuminate\Http\Request;

/** Session cart: show, add, change quantity, remove. */
class CartController extends Controller
{
    // Laravel "injects" the Cart service automatically into each method.

    public function index(Cart $cart)
    {
        return view('cart.index', ['cart' => $cart->summary()]);
    }

    public function store(Request $request, Cart $cart)
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:'.config('shop.max_quantity')],
        ]);

        $product = Product::available()->findOrFail($data['product_id']);
        $newQuantity = $cart->quantityOf($product->id) + ($data['quantity'] ?? 1);

        if ($newQuantity > $product->stock) {
            $message = "Stock insuffisant : il reste {$product->stock} exemplaire(s) de « {$product->designation} ».";

            // Called from JavaScript (fetch)? Answer in JSON, otherwise redirect as usual.
            return $request->expectsJson()
                ? response()->json(['message' => $message, 'cartCount' => $cart->count()], 422)
                : back()->with('error', $message);
        }

        $cart->set($product->id, min($newQuantity, config('shop.max_quantity')));
        $message = "« {$product->designation} » a été ajouté au panier.";

        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'cartCount' => $cart->count()]);
        }

        return back()->with('success', $message);
    }

    public function update(Request $request, Cart $cart, Product $product)
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:0', 'max:'.config('shop.max_quantity')],
        ]);

        if ($data['quantity'] > $product->stock) {
            return back()->with('error', "Stock insuffisant : il reste {$product->stock} exemplaire(s) de « {$product->designation} ».");
        }

        $cart->set($product->id, $data['quantity']);

        return back()->with('success', 'Panier mis à jour.');
    }

    public function destroy(Cart $cart, Product $product)
    {
        $cart->remove($product->id);

        return back()->with('success', "« {$product->designation} » a été retiré du panier.");
    }
}
