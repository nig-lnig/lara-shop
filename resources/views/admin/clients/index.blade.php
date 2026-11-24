@extends('layouts.admin')

@section('title', 'Clients')

@section('content')
    <div class="page-head">
        <h1>Clients</h1>
        <div class="actions">
            <a href="{{ route('admin.clients.create') }}" class="btn btn-primary">+ Nouveau client</a>
        </div>
    </div>

    <form method="get" class="card filter-bar">
        <div class="field">
            <label for="q">Recherche</label>
            <input id="q" type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Nom, code Cli-…, ville">
        </div>
        <div class="field">
            <label for="type">Type</label>
            <select id="type" name="type">
                <option value="">Tous les types</option>
                @foreach (App\Models\Client::TYPES as $code => $label)
                    <option value="{{ $code }}" @selected(($filters['type'] ?? '') === $code)>{{ $code }} – {{ $label }}</option>
                @endforeach
            </select>
        </div>
        @include('admin._archive-filter')
        <div class="filter-buttons">
            <button type="submit" class="btn btn-primary">Filtrer</button>
            <a href="{{ route('admin.clients.index') }}" class="btn btn-ghost">Réinitialiser</a>
        </div>
    </form>

    @if ($clients->isEmpty())
        <div class="empty-state card"><p>Aucun client ne correspond à ces critères.</p></div>
    @else
        <div class="card table-card">
            @include('admin._table-toolbar', ['paginator' => $clients, 'noun' => 'client(s)', 'dataset' => 'clients'])
            <div class="table-wrap">
            <table class="table table-responsive">
                <thead>
                <tr>
                    <th>Code</th><th>Nom</th><th>Ville</th><th>Type</th><th class="num">Remise</th>
                    <th>Compte</th><th class="num">Commandes</th><th><span class="sr-only">Actions</span></th>
                </tr>
                </thead>
                <tbody>
                @foreach ($clients as $client)
                    <tr @class(['is-archived' => $client->isArchived()])>
                        <td data-label="Code"><strong>{{ $client->code }}</strong></td>
                        <td data-label="Nom"><span>{{ $client->name }} @if ($client->isArchived())<span class="tag">archivé</span>@endif</span></td>
                        <td data-label="Ville">{{ $client->postal_code }} {{ $client->city }}</td>
                        <td data-label="Type"><span>{{ $client->type }} <span class="muted small">{{ $client->typeLabel() }}</span></span></td>
                        <td data-label="Remise" class="num">{{ $client->special_discount }} %</td>
                        <td data-label="Compte">{{ $client->user?->email ?? '—' }}</td>
                        <td data-label="Commandes" class="num">
                            <a href="{{ route('admin.orders.index', ['q' => $client->code]) }}">{{ $client->orders_count }}</a>
                        </td>
                        <td>@include('admin._row-actions', ['item' => $client, 'routePrefix' => 'admin.clients', 'label' => 'le client '.$client->code])</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            </div>
        </div>
        {{ $clients->links() }}
    @endif
@endsection
