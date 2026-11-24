{{-- Printable order summary. "Imprimer" -> the browser can also "Enregistrer au format PDF". --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Commande {{ $order->number }} · {{ config('shop.name') }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="print-page">
<main class="print-sheet">
    <div class="no-print print-toolbar">
        <button type="button" class="btn btn-primary" onclick="window.print()">Imprimer / Enregistrer en PDF</button>
        <a href="{{ route('orders.show', $order) }}" class="btn btn-ghost">Retour</a>
    </div>

    <header class="print-header">
        <div>
            <p class="brand"><span class="brand-mark" aria-hidden="true">L</span> {{ config('shop.name') }}</p>
            <p class="muted">Récapitulatif de commande</p>
        </div>
        <div class="print-meta">
            <h1>{{ $order->number }}</h1>
            <p>Date : {{ fr_date($order->order_date) }}</p>
            <p>Statut : {{ $order->statusLabel() }}</p>
        </div>
    </header>

    <section class="print-columns">
        <div>
            <h2>Client</h2>
            <p>
                <strong>{{ $order->client->name }}</strong> ({{ $order->client->code }})<br>
                {{ $order->client->street }}<br>
                {{ $order->client->postal_code }} {{ $order->client->city }}
            </p>
        </div>
        <div>
            <h2>Paiement</h2>
            <p>{{ $order->paymentTermsLabel() }} ({{ $order->payment_terms }})</p>
            <p>Remise appliquée : {{ $order->discount_rate }} %</p>
        </div>
    </section>

    @include('orders._lines')

    <p class="muted small">Prix unitaires et remise enregistrés au moment de la commande.</p>
</main>
</body>
</html>
