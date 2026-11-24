@extends('layouts.admin')

@section('title', 'Produits')

@section('content')
    <div class="page-head">
        <h1>Produits et stock</h1>
        <div class="actions">
            <a href="{{ route('admin.products.create') }}" class="btn btn-primary">+ Nouveau produit</a>
        </div>
    </div>

    <form method="get" class="card filter-bar">
        <div class="field">
            <label for="q">Recherche</label>
            <input id="q" type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Désignation ou code Pr-…">
        </div>
        <div class="field">
            <label for="family">Famille</label>
            <select id="family" name="family">
                <option value="">Toutes</option>
                @foreach (App\Models\Product::FAMILIES as $code => $label)
                    <option value="{{ $code }}" @selected(($filters['family'] ?? '') === $code)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="supplier_id">Fournisseur</label>
            <select id="supplier_id" name="supplier_id">
                <option value="">Tous</option>
                @foreach ($suppliers as $supplier)
                    <option value="{{ $supplier->id }}" @selected(($filters['supplier_id'] ?? '') == $supplier->id)>{{ $supplier->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="stock">Stock</label>
            <select id="stock" name="stock">
                <option value="">Tous</option>
                <option value="low" @selected(($filters['stock'] ?? '') === 'low')>Stock faible</option>
            </select>
        </div>
        <div class="field">
            <label for="visibility">Boutique</label>
            <select id="visibility" name="visibility">
                <option value="">Tous</option>
                <option value="listed" @selected(($filters['visibility'] ?? '') === 'listed')>Visibles</option>
                <option value="hidden" @selected(($filters['visibility'] ?? '') === 'hidden')>Masqués</option>
            </select>
        </div>
        @include('admin._archive-filter')
        <div class="filter-buttons">
            <button type="submit" class="btn btn-primary">Filtrer</button>
            <a href="{{ route('admin.products.index') }}" class="btn btn-ghost">Réinitialiser</a>
        </div>
    </form>

    @if ($products->isEmpty())
        <div class="empty-state card"><p>Aucun produit ne correspond à ces critères.</p></div>
    @else
        <div class="card table-card">
            @include('admin._table-toolbar', ['paginator' => $products, 'noun' => 'produit(s)', 'dataset' => 'produits'])
            <div class="table-wrap">
            <table class="table table-responsive">
                <thead>
                <tr>
                    <th><span class="sr-only">Image</span></th><th>Code</th><th>Désignation</th><th>Famille</th>
                    <th class="num">Prix</th><th class="num">Stock</th><th>Fournisseur</th><th><span class="sr-only">Actions</span></th>
                </tr>
                </thead>
                <tbody>
                @foreach ($products as $product)
                    <tr @class(['is-archived' => $product->isArchived()])>
                        <td><x-product-image :product="$product" size="xs" /></td>
                        <td data-label="Code"><strong>{{ $product->code }}</strong></td>
                        <td data-label="Désignation"><span>{{ $product->designation }} @if ($product->isArchived())<span class="tag">archivé</span>@endif @unless ($product->is_listed)<span class="tag tag-hidden" title="Non visible pour les clients">masqué</span>@endunless</span></td>
                        <td data-label="Famille">{{ $product->familyLabel() }}</td>
                        <td data-label="Prix" class="num">{{ money($product->unit_price) }}</td>
                        <td data-label="Stock" class="num">
                            @if ($product->isLowStock())
                                <span class="stock stock-pill {{ $product->stock === 0 ? 'stock-out' : 'stock-low' }}" title="Seuil d’alerte : {{ $product->low_stock_threshold }}">{{ $product->stock }} · stock faible</span>
                            @else
                                {{ $product->stock }}
                            @endif
                        </td>
                        <td data-label="Fournisseur">{{ $product->supplier->name }}</td>
                        <td>@include('admin._row-actions', ['item' => $product, 'routePrefix' => 'admin.products', 'label' => 'le produit '.$product->code])</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            </div>
        </div>
        {{ $products->links() }}
    @endif
@endsection
