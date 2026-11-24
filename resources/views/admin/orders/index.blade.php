@extends('layouts.admin')

@section('title', 'Commandes')

@section('content')
    <div class="page-head">
        <div>
            <h1>Commandes</h1>
            <p class="muted">Filtrez la liste puis exportez exactement ces résultats depuis la barre du tableau.</p>
        </div>
    </div>

    <form method="get" class="card filter-bar">
        <div class="field">
            <label for="q">Recherche</label>
            <input id="q" type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="N° Com-…, client ou code Cli-…">
        </div>
        <div class="field">
            <label for="status">Statut</label>
            <select id="status" name="status">
                <option value="">Tous</option>
                @foreach (App\Models\Order::STATUSES as $code => $label)
                    <option value="{{ $code }}" @selected(($filters['status'] ?? '') === $code)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="payment_terms">Paiement</label>
            <select id="payment_terms" name="payment_terms">
                <option value="">Tous</option>
                @foreach (App\Models\Order::PAYMENT_TERMS as $code => $label)
                    <option value="{{ $code }}" @selected(($filters['payment_terms'] ?? '') === $code)>{{ $code }} – {{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="from">Du</label>
            <input id="from" type="date" name="from" value="{{ $filters['from'] ?? '' }}">
        </div>
        <div class="field">
            <label for="to">Au</label>
            <input id="to" type="date" name="to" value="{{ $filters['to'] ?? '' }}">
        </div>
        <div class="filter-buttons">
            <button type="submit" class="btn btn-primary">Filtrer</button>
            <a href="{{ route('admin.orders.index') }}" class="btn btn-ghost">Réinitialiser</a>
        </div>
    </form>

    @if ($orders->isEmpty())
        <div class="empty-state card"><p>Aucune commande ne correspond à ces critères.</p></div>
    @else
        <div class="card table-card">
            @include('admin._table-toolbar', ['paginator' => $orders, 'noun' => 'commande(s)', 'dataset' => 'commandes', 'extraExports' => ['lignes-commande' => 'Leurs lignes de commande']])
            <div class="table-wrap">
            <table class="table table-responsive">
                <thead>
                <tr>
                    <th>N°</th><th>Date</th><th>Client</th><th>Paiement</th><th class="num">Lignes</th>
                    <th class="num">Total</th><th>Statut</th><th><span class="sr-only">Actions</span></th>
                </tr>
                </thead>
                <tbody>
                @foreach ($orders as $order)
                    <tr>
                        <td data-label="N°"><strong>{{ $order->number }}</strong></td>
                        <td data-label="Date">{{ fr_date($order->order_date) }}</td>
                        <td data-label="Client"><span>{{ $order->client->name }} <span class="muted small">{{ $order->client->code }}</span></span></td>
                        <td data-label="Paiement">{{ $order->payment_terms }}</td>
                        <td data-label="Lignes" class="num">{{ $order->lines_count }}</td>
                        <td data-label="Total" class="num">{{ money($order->total) }}</td>
                        <td data-label="Statut"><x-status-badge :order="$order" /></td>
                        <td><a href="{{ route('admin.orders.show', $order) }}" class="btn btn-outline btn-sm">Détails</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            </div>
        </div>
        {{ $orders->links() }}
    @endif
@endsection
