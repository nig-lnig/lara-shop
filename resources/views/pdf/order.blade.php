{{-- PDF order summary ("Récapitulatif de commande"). Needs $order with lines.product and client. --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Commande {{ $order->number }}</title>
    @include('pdf._styles')
</head>
<body>
<table class="doc-header">
    <tr>
        <td><table class="brand"><tr><td class="brand-mark">L</td><td class="brand-name">{{ config('shop.name') }}</td></tr></table>
            <div class="muted small" style="margin-top: 4px">Papeterie · Bureautique · Fournitures · Librairie</div></td>
        <td class="doc-title">
            <div class="label">Récapitulatif de commande</div>
            <h1>{{ $order->number }}</h1>
            <div class="muted small">Passée le {{ fr_date($order->order_date) }}</div>
        </td>
    </tr>
</table>

<table class="boxes">
    <tr>
        <td class="box">
            <div class="label">Client</div>
            <strong>{{ $order->client->name }}</strong><br>
            <span class="muted">{{ $order->client->code }}</span><br>
            {{ $order->client->street }}<br>
            {{ $order->client->postal_code }} {{ $order->client->city }}
        </td>
        <td class="box">
            <div class="label">Condition de paiement</div>
            <strong>{{ $order->paymentTermsLabel() }}</strong> <span class="muted">({{ $order->payment_terms }})</span><br><br>
            <div class="label">Remise client</div>
            {{ $order->discount_rate }} %
        </td>
        <td class="box">
            <div class="label">Statut</div>
            <span class="badge">{{ $order->statusLabel() }}</span><br><br>
            <div class="label">Articles</div>
            {{ $order->lines->sum('quantity') }} article(s), {{ $order->lines->count() }} ligne(s)
        </td>
    </tr>
</table>

<table class="data">
    <thead>
    <tr>
        <th>Code produit</th>
        <th>Désignation</th>
        <th class="num">Prix unitaire</th>
        <th class="num">Quantité</th>
        <th class="num">Total ligne</th>
    </tr>
    </thead>
    <tbody>
    @foreach ($order->lines as $line)
        <tr>
            <td>{{ $line->product->code }}</td>
            <td>{{ $line->product->designation }}</td>
            <td class="num">{{ money($line->unit_price) }}</td>
            <td class="num">{{ $line->quantity }}</td>
            <td class="num">{{ money($line->line_total) }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<table class="totals">
    <tr><td>Sous-total</td><td class="num">{{ money($order->subtotal) }}</td></tr>
    <tr><td>Remise client ({{ $order->discount_rate }} %)</td><td class="num discount">{{ $order->discount_amount > 0 ? '− ' : '' }}{{ money($order->discount_amount) }}</td></tr>
    <tr class="grand"><td><strong>Total</strong></td><td class="num big">{{ money($order->total) }}</td></tr>
</table>

<p class="muted small" style="margin-top: 22px">
    Prix unitaires et remise enregistrés au moment de la commande ; ils ne changent pas si le catalogue évolue.
    Aucun paiement en ligne : règlement {{ $order->payment_terms === 'LIV' ? 'à la livraison' : 'à 30 jours' }}.
</p>
</body>
</html>
