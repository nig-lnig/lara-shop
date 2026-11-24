@extends('layouts.admin')

@section('title', 'Fournisseurs')

@section('content')
    <div class="page-head">
        <h1>Fournisseurs</h1>
        <div class="actions">
            <a href="{{ route('admin.suppliers.create') }}" class="btn btn-primary">+ Nouveau fournisseur</a>
        </div>
    </div>

    <form method="get" class="card filter-bar">
        <div class="field">
            <label for="q">Recherche</label>
            <input id="q" type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Nom, code Fr-…, ville">
        </div>
        @include('admin._archive-filter')
        <div class="filter-buttons">
            <button type="submit" class="btn btn-primary">Filtrer</button>
            <a href="{{ route('admin.suppliers.index') }}" class="btn btn-ghost">Réinitialiser</a>
        </div>
    </form>

    @if ($suppliers->isEmpty())
        <div class="empty-state card"><p>Aucun fournisseur ne correspond à ces critères.</p></div>
    @else
        <div class="card table-card">
            @include('admin._table-toolbar', ['paginator' => $suppliers, 'noun' => 'fournisseur(s)', 'dataset' => 'fournisseurs'])
            <div class="table-wrap">
            <table class="table table-responsive">
                <thead><tr><th>Code</th><th>Nom</th><th>Ville</th><th class="num">Produits</th><th><span class="sr-only">Actions</span></th></tr></thead>
                <tbody>
                @foreach ($suppliers as $supplier)
                    <tr @class(['is-archived' => $supplier->isArchived()])>
                        <td data-label="Code"><strong>{{ $supplier->code }}</strong></td>
                        <td data-label="Nom"><span>{{ $supplier->name }} @if ($supplier->isArchived())<span class="tag">archivé</span>@endif</span></td>
                        <td data-label="Ville">{{ $supplier->city }}</td>
                        <td data-label="Produits" class="num">
                            <a href="{{ route('admin.products.index', ['supplier_id' => $supplier->id]) }}">{{ $supplier->products_count }}</a>
                        </td>
                        <td>@include('admin._row-actions', ['item' => $supplier, 'routePrefix' => 'admin.suppliers', 'label' => 'le fournisseur '.$supplier->code])</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            </div>
        </div>
        {{ $suppliers->links() }}
    @endif
@endsection
