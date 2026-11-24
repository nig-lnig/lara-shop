{{-- One form for "create" and "edit": $client->exists tells which one. --}}
@extends('layouts.admin')

@section('title', $client->exists ? 'Modifier '.$client->code : 'Nouveau client')

@section('content')
    <a href="{{ route('admin.clients.index') }}" class="small-link"><x-icon name="arrow-left" /> Clients</a>
    <h1>{{ $client->exists ? 'Modifier le client '.$client->code : 'Nouveau client' }}</h1>

    <form action="{{ $client->exists ? route('admin.clients.update', $client) : route('admin.clients.store') }}"
          method="post" class="card stack form-narrow">
        @csrf
        @if ($client->exists) @method('PUT') @endif

        @unless ($client->exists)
            <p class="muted small">Le code client (Cli-…) est généré automatiquement à l’enregistrement.</p>
        @endunless

        <x-input name="name" label="Nom client" :value="$client->name" required />
        <x-input name="street" label="Rue" :value="$client->street" required />
        <div class="row-2">
            <x-input name="postal_code" label="Code postal" :value="$client->postal_code" required />
            <x-input name="city" label="Ville" :value="$client->city" required />
        </div>
        <div class="row-2">
            <x-select name="type" label="Type client" required :value="$client->type"
                      :options="collect(App\Models\Client::TYPES)->map(fn ($label, $code) => $code.' – '.$label)->all()" />
            <x-input name="special_discount" label="Remise spéciale (%)" type="number" min="0" max="100" step="1"
                     :value="$client->special_discount" required help="Entier de 0 à 100. S’applique aux prochaines commandes." />
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Enregistrer</button>
            <a href="{{ route('admin.clients.index') }}" class="btn btn-ghost">Annuler</a>
        </div>
    </form>
@endsection
