<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

/** Public product catalog: list with search / filters / sorting, and detail page. */
class CatalogController extends Controller
{
    public function index(Request $request)
    {
        // Read the filters from the URL (?q=...&family=...). Invalid values are
        // simply dropped, so a hand-edited URL can never break the page.
        $get = fn (string $key) => is_string($request->query($key)) ? trim($request->query($key)) : '';

        $filters = array_filter([
            'q' => mb_substr($get('q'), 0, 100),
            'family' => array_key_exists($get('family'), Product::FAMILIES) ? $get('family') : null,
            'min_price' => is_numeric($get('min_price')) ? max(0, (float) $get('min_price')) : null,
            'max_price' => is_numeric($get('max_price')) ? max(0, (float) $get('max_price')) : null,
            'sort' => array_key_exists($get('sort'), Product::SORTS) ? $get('sort') : null,
            'in_stock' => $get('in_stock') === '1' ? '1' : null,
        ], fn ($value) => $value !== null && $value !== '');

        $products = Product::available()
            ->with('supplier')
            ->filter($filters)
            ->sortBy($filters['sort'] ?? null)
            ->paginate(config('shop.per_page'))
            ->withQueryString(); // keeps the filters in the pagination links

        $favoriteIds = $request->user()?->favoriteProducts()->pluck('products.id')->all() ?? [];

        // Number of products per family, shown next to each family in the filters.
        $familyCounts = Product::available()->selectRaw('family, count(*) as total')
            ->groupBy('family')->pluck('total', 'family');

        return view('catalog.index', compact('products', 'filters', 'favoriteIds', 'familyCounts'));
    }

    public function show(Request $request, Product $product)
    {
        // Hidden or archived products: 404 for customers. Admins may preview them.
        abort_unless($product->isPurchasable() || $request->user()?->isAdmin(), 404);

        $product->load('supplier');
        $isFavorite = $request->user()?->favoriteProducts()->whereKey($product->id)->exists() ?? false;

        $related = Product::available()
            ->where('family', $product->family)
            ->whereKeyNot($product->id)
            ->inRandomOrder()->limit(4)->get();

        return view('catalog.show', compact('product', 'isFavorite', 'related'));
    }
}
