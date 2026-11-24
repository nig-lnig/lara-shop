<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

/** A simple favorites list for customers. */
class FavoriteController extends Controller
{
    public function index(Request $request)
    {
        $products = $request->user()->favoriteProducts()->available()->orderBy('designation')->get();

        return view('favorites.index', compact('products'));
    }

    /** Adds the product to favorites, or removes it if it is already there. */
    public function toggle(Request $request, Product $product)
    {
        $result = $request->user()->favoriteProducts()->toggle($product->id);

        $isFavorite = $result['attached'] !== [];
        $message = $isFavorite
            ? "« {$product->designation} » a été ajouté à vos favoris."
            : "« {$product->designation} » a été retiré de vos favoris.";

        // Called from JavaScript (fetch)? Answer in JSON, otherwise redirect as usual.
        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'favorite' => $isFavorite]);
        }

        return back()->with('success', $message);
    }
}
