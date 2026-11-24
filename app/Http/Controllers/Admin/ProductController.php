<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/** Admin CRUD for products (PDF: Produit) with stock and image. */
class ProductController extends Controller
{
    public const FILTERS = ['q', 'family', 'supplier_id', 'stock', 'visibility', 'archived'];

    public function index(Request $request)
    {
        $filters = $this->filters($request, self::FILTERS);

        $products = Product::filter($filters)
            ->archiveState($filters['archived'] ?? null)
            ->with('supplier')
            ->orderBy('id')
            ->paginate(config('shop.admin_per_page'))
            ->withQueryString();

        $suppliers = Supplier::orderBy('name')->get(['id', 'code', 'name']);

        return view('admin.products.index', compact('products', 'filters', 'suppliers'));
    }

    public function create()
    {
        return view('admin.products.form', [
            'product' => new Product(['stock' => 0, 'low_stock_threshold' => 5]),
            'suppliers' => Supplier::active()->orderBy('name')->get(),
        ]);
    }

    public function store(ProductRequest $request)
    {
        $data = $request->safe()->except(['image', 'remove_image']);

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('products', 'public');
        }

        $product = Product::create($data);

        return redirect()->route('admin.products.index')->with('success', "Produit {$product->code} créé.");
    }

    public function edit(Product $product)
    {
        return view('admin.products.form', [
            'product' => $product,
            // Active suppliers + the current one (even if it was archived since).
            'suppliers' => Supplier::active()->orWhere('id', $product->supplier_id)->orderBy('name')->get(),
        ]);
    }

    public function update(ProductRequest $request, Product $product)
    {
        // Changing the price affects FUTURE orders only:
        // past order lines keep their own unit_price.
        $data = $request->safe()->except(['image', 'remove_image']);

        if ($request->boolean('remove_image') || $request->hasFile('image')) {
            if ($product->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }
            $data['image_path'] = null;
        }

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('products', 'public');
        }

        $product->update($data);

        return redirect()->route('admin.products.index')->with('success', "Produit {$product->code} mis à jour.");
    }

    /** A product that appears in orders is archived (hidden from the shop), never deleted. */
    public function destroy(Product $product)
    {
        if ($product->orderLines()->exists()) {
            $product->archive();

            return back()->with('success', "Produit {$product->code} archivé (il figure dans des commandes, il n’a pas été supprimé).");
        }

        if ($product->image_path) {
            Storage::disk('public')->delete($product->image_path);
        }
        $product->delete();

        return back()->with('success', "Produit {$product->code} supprimé.");
    }

    public function restore(Product $product)
    {
        $product->unarchive();

        return back()->with('success', "Produit {$product->code} restauré.");
    }
}
