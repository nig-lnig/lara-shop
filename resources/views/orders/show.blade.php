@extends('layouts.app')

@section('title', 'Commande '.$order->number)

@section('content')
    @if (session('just_ordered'))
        <div class="confirmation card" role="status">
            <div class="confirmation-icon" aria-hidden="true"><x-icon name="check" /></div>
            <h1>Merci, votre commande est confirmée</h1>
            <p>Numéro de commande : <span class="order-number">{{ $order->number }}</span></p>
            <p class="muted">Total {{ money($order->total) }} · {{ $order->paymentTermsLabel() }}. Conservez ce numéro pour suivre la commande.</p>
            <div class="actions" style="justify-content: center">
                <a href="{{ route('orders.pdf', $order) }}" class="btn btn-outline"><x-icon name="download" /> Récapitulatif PDF</a>
                <a href="{{ route('catalog.index') }}" class="btn btn-primary">Continuer mes achats</a>
            </div>
        </div>
    @else
        @include('partials.account-nav')
    @endif

    <div class="page-head">
        <div>
            <a href="{{ route('orders.index') }}" class="small-link"><x-icon name="arrow-left" /> Mes commandes</a>
            <h1>Commande <span class="code">{{ $order->number }}</span></h1>
            <p class="muted">Passée le {{ fr_date($order->order_date) }}</p>
        </div>
        <div class="actions">
            <a href="{{ route('orders.pdf', $order) }}" class="btn btn-outline"><x-icon name="download" /> Télécharger le PDF</a>
            <a href="{{ route('orders.print', $order) }}" class="btn btn-ghost" target="_blank"><x-icon name="printer" /> Imprimer</a>
            @can('cancel', $order)
                <form action="{{ route('orders.cancel', $order) }}" method="post" data-confirm="Annuler la commande {{ $order->number }} ?">
                    @csrf
                    <button type="submit" class="btn btn-danger-ghost">Annuler la commande</button>
                </form>
            @endcan
        </div>
    </div>

    @include('orders._tracking')

    <div class="info-grid">
        <div class="card">
            <p class="eyebrow">Statut</p>
            <x-status-badge :order="$order" />
        </div>
        <div class="card">
            <p class="eyebrow">Condition de paiement</p>
            <p style="margin: 0">{{ $order->paymentTermsLabel() }} <span class="muted">({{ $order->payment_terms }})</span></p>
        </div>
        <div class="card">
            <p class="eyebrow">Livraison</p>
            <address class="address">
                {{ $order->client->name }}<br>{{ $order->client->street }}<br>
                {{ $order->client->postal_code }} {{ $order->client->city }}
            </address>
        </div>
    </div>

    <section class="card">
        <h2>Articles</h2>
        @include('orders._lines')
    </section>
@endsection
