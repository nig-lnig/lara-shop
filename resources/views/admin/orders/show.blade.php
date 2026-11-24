@extends('layouts.admin')

@section('title', 'Commande '.$order->number)

@section('content')
    <a href="{{ route('admin.orders.index') }}" class="small-link no-print"><x-icon name="arrow-left" /> Commandes</a>
    <div class="page-head">
        <div>
            <h1>Commande {{ $order->number }}</h1>
            <p class="muted">Passée le {{ fr_date($order->order_date) }}</p>
        </div>
        <div class="actions no-print">
            <a href="{{ route('admin.orders.pdf', $order) }}" class="btn btn-outline"><x-icon name="download" /> Télécharger le PDF</a>
            <button type="button" class="btn btn-ghost" onclick="window.print()"><x-icon name="printer" /> Imprimer</button>
        </div>
    </div>

    <div class="info-grid">
        <div class="card">
            <h2>Client</h2>
            <p>
                <a href="{{ route('admin.clients.edit', $order->client) }}"><strong>{{ $order->client->name }}</strong></a>
                ({{ $order->client->code }})<br>
                {{ $order->client->street }}<br>{{ $order->client->postal_code }} {{ $order->client->city }}<br>
                <span class="muted small">{{ $order->client->typeLabel() }} · {{ $order->client->user?->email ?? 'sans compte' }}</span>
            </p>
        </div>
        <div class="card">
            <h2>Paiement</h2>
            <p>{{ $order->paymentTermsLabel() }} ({{ $order->payment_terms }})</p>
            <p class="muted small">Remise appliquée : {{ $order->discount_rate }} %</p>
        </div>
        <div class="card no-print">
            <h2>Statut</h2>
            <p><x-status-badge :order="$order" /></p>
            @if ($order->nextStatuses())
                <form action="{{ route('admin.orders.status', $order) }}" method="post" class="status-form"
                      data-confirm-if="cancelled" data-confirm="Annuler cette commande ? Le stock sera restitué.">
                    @csrf
                    @method('PATCH')
                    <label for="status" class="sr-only">Nouveau statut</label>
                    <select id="status" name="status" required>
                        @foreach ($order->nextStatuses() as $code => $label)
                            <option value="{{ $code }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm">Mettre à jour</button>
                </form>
            @else
                <p class="muted small">Statut final : aucune modification possible.</p>
            @endif
            {{-- Confirmation shown right next to the form that changed the status --}}
            @if (session('status_updated'))
                <p class="status-confirm" role="status"><x-icon name="check" /> {{ session('status_updated') }}</p>
            @endif
            @error('status')
                <p class="field-error" style="margin-top: .6rem"><x-icon name="alert" /> {{ $message }}</p>
            @enderror
            @if ($order->stock_restored_at)
                <p class="muted small">Stock restitué le {{ fr_date($order->stock_restored_at) }}.</p>
            @endif
        </div>
    </div>

    <section class="card">
        <h2>Lignes de commande</h2>
        @include('orders._lines')
    </section>
@endsection
