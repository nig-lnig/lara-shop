{{--
    Order lines table + totals. Shared by the customer page, the admin page and the print page.
    Uses the prices STORED in the order (not the current catalog prices).
--}}
<div class="table-wrap">
    <table class="table table-responsive">
        <caption class="sr-only">Lignes de la commande {{ $order->number }}</caption>
        <thead>
        <tr>
            <th scope="col">Code produit</th>
            <th scope="col">Désignation</th>
            <th scope="col" class="num">Prix unitaire</th>
            <th scope="col" class="num">Quantité</th>
            <th scope="col" class="num">Total ligne</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($order->lines as $line)
            <tr>
                <td data-label="Code">{{ $line->product->code }}</td>
                <td data-label="Désignation">{{ $line->product->designation }}</td>
                <td data-label="Prix unitaire" class="num">{{ money($line->unit_price) }}</td>
                <td data-label="Quantité" class="num">{{ $line->quantity }}</td>
                <td data-label="Total ligne" class="num">{{ money($line->line_total) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

<div class="totals-wrap">
    @include('partials.totals', ['totals' => $order])
</div>
