<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;

/** Admin CRUD for suppliers (PDF: Fournisseur). */
class SupplierController extends Controller
{
    public const FILTERS = ['q', 'archived'];

    private function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'city' => ['required', 'string', 'max:100'],
        ];
    }

    public function index(Request $request)
    {
        $filters = $this->filters($request, self::FILTERS);

        $suppliers = Supplier::filter($filters)
            ->archiveState($filters['archived'] ?? null)
            ->withCount('products')
            ->orderBy('id')
            ->paginate(config('shop.admin_per_page'))
            ->withQueryString();

        return view('admin.suppliers.index', compact('suppliers', 'filters'));
    }

    public function create()
    {
        return view('admin.suppliers.form', ['supplier' => new Supplier]);
    }

    public function store(Request $request)
    {
        $supplier = Supplier::create($request->validate($this->rules()));

        return redirect()->route('admin.suppliers.index')->with('success', "Fournisseur {$supplier->code} créé.");
    }

    public function edit(Supplier $supplier)
    {
        return view('admin.suppliers.form', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        $supplier->update($request->validate($this->rules()));

        return redirect()->route('admin.suppliers.index')->with('success', "Fournisseur {$supplier->code} mis à jour.");
    }

    /** A supplier with products is archived, never deleted (rule 6: each product keeps its supplier). */
    public function destroy(Supplier $supplier)
    {
        if ($supplier->products()->exists()) {
            $supplier->archive();

            return back()->with('success', "Fournisseur {$supplier->code} archivé (il fournit des produits, il n’a pas été supprimé).");
        }

        $supplier->delete();

        return back()->with('success', "Fournisseur {$supplier->code} supprimé.");
    }

    public function restore(Supplier $supplier)
    {
        $supplier->unarchive();

        return back()->with('success', "Fournisseur {$supplier->code} restauré.");
    }
}
