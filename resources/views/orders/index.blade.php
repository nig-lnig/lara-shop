@extends('layouts.app')

@section('title', 'Mes commandes')

@section('content')
    <h1>Mon compte</h1>
    @include('partials.account-nav')

    @if ($orders->isEmpty())
        <div class="empty-state card">
            <x-icon name="box" />
            <h2>Aucune commande pour le moment</h2>
            <p>Vos commandes apparaîtront ici avec leur statut de suivi.</p>
            <a href="{{ route('catalog.index') }}" class="btn btn-primary">Commencer mes achats</a>
        </div>
    @else
        <div class="card table-card">
            <div class="table-toolbar">
                <p><strong>{{ $orders->total() }}</strong> commande(s)</p>
                <x-export-formats route="my-data.download" dataset="commandes" label="Télécharger" />
            </div>
            <div class="table-wrap">
                <table class="table table-responsive">
                    <thead>
                    <tr>
                        <th scope="col">N° commande</th>
                        <th scope="col">Date</th>
                        <th scope="col">Articles</th>
                        <th scope="col">Paiement</th>
                        <th scope="col" class="num">Total</th>
                        <th scope="col">Statut</th>
                        <th scope="col"><span class="sr-only">Actions</span></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($orders as $order)
                        <tr>
                            <td data-label="N°"><a href="{{ route('orders.show', $order) }}"><strong>{{ $order->number }}</strong></a></td>
                            <td data-label="Date">{{ fr_date($order->order_date) }}</td>
                            <td data-label="Articles">{{ $order->lines_count }} ligne(s)</td>
                            <td data-label="Paiement">{{ $order->paymentTermsLabel() }}</td>
                            <td data-label="Total" class="num"><strong>{{ money($order->total) }}</strong></td>
                            <td data-label="Statut"><x-status-badge :order="$order" /></td>
                            <td><a href="{{ route('orders.show', $order) }}" class="btn btn-outline btn-sm">Détails</a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        {{ $orders->links() }}
    @endif
@endsection
