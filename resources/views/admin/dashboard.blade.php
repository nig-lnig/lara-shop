@extends('layouts.admin')

@section('title', 'Tableau de bord')

@section('content')
    <h1>Tableau de bord</h1>

    <div class="stats-grid">
        <a href="{{ route('admin.orders.index') }}" class="card stat">
            <span class="stat-value">{{ $stats['orders'] }}</span><span class="stat-label">commandes</span>
        </a>
        <a href="{{ route('admin.orders.index', ['status' => 'pending']) }}" class="card stat stat-warning">
            <span class="stat-value">{{ $stats['pending'] }}</span><span class="stat-label">en attente</span>
        </a>
        <div class="card stat">
            <span class="stat-value">{{ money($stats['revenue']) }}</span><span class="stat-label">chiffre d’affaires (hors annulées)</span>
        </div>
        <a href="{{ route('admin.clients.index') }}" class="card stat">
            <span class="stat-value">{{ $stats['clients'] }}</span><span class="stat-label">clients actifs</span>
        </a>
        <a href="{{ route('admin.products.index') }}" class="card stat">
            <span class="stat-value">{{ $stats['products'] }}</span><span class="stat-label">produits en vente</span>
        </a>
        <a href="{{ route('admin.suppliers.index') }}" class="card stat">
            <span class="stat-value">{{ $stats['suppliers'] }}</span><span class="stat-label">fournisseurs</span>
        </a>
    </div>

    <div class="admin-columns">
        <section class="card">
            <div class="section-head">
                <h2>Commandes récentes</h2>
                <a href="{{ route('admin.orders.index') }}" class="small-link">Toutes les commandes <x-icon name="arrow-right" /></a>
            </div>
            @if ($recentOrders->isEmpty())
                <p class="muted">Aucune commande.</p>
            @else
                <div class="table-wrap">
                    <table class="table table-responsive">
                        <thead><tr><th>N°</th><th>Date</th><th>Client</th><th class="num">Total</th><th>Statut</th></tr></thead>
                        <tbody>
                        @foreach ($recentOrders as $order)
                            <tr>
                                <td data-label="N°"><a href="{{ route('admin.orders.show', $order) }}">{{ $order->number }}</a></td>
                                <td data-label="Date">{{ fr_date($order->order_date) }}</td>
                                <td data-label="Client">{{ $order->client->name }}</td>
                                <td data-label="Total" class="num">{{ money($order->total) }}</td>
                                <td data-label="Statut"><x-status-badge :order="$order" /></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section class="card">
            <div class="section-head">
                <h2>Stock faible</h2>
                <a href="{{ route('admin.products.index', ['stock' => 'low']) }}" class="small-link">Voir tout <x-icon name="arrow-right" /></a>
            </div>
            @if ($lowStock->isEmpty())
                <p class="muted">Aucun produit sous son seuil d’alerte.</p>
            @else
                <ul class="low-stock-list">
                    @foreach ($lowStock as $product)
                        <li>
                            <x-product-image :product="$product" size="xs" />
                            <div>
                                <a href="{{ route('admin.products.edit', $product) }}">{{ $product->designation }}</a><br>
                                <span class="muted small"><span class="code">{{ $product->code }}</span> · seuil {{ $product->low_stock_threshold }}</span>
                            </div>
                            <span class="stock stock-pill {{ $product->stock === 0 ? 'stock-out' : 'stock-low' }}">{{ $product->stock }} en stock</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
@endsection
