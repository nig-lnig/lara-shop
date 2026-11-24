@extends('layouts.admin')

@section('title', $supplier->exists ? 'Modifier '.$supplier->code : 'Nouveau fournisseur')

@section('content')
    <a href="{{ route('admin.suppliers.index') }}" class="small-link"><x-icon name="arrow-left" /> Fournisseurs</a>
    <h1>{{ $supplier->exists ? 'Modifier le fournisseur '.$supplier->code : 'Nouveau fournisseur' }}</h1>

    <form action="{{ $supplier->exists ? route('admin.suppliers.update', $supplier) : route('admin.suppliers.store') }}"
          method="post" class="card stack form-narrow">
        @csrf
        @if ($supplier->exists) @method('PUT') @endif

        @unless ($supplier->exists)
            <p class="muted small">Le code fournisseur (Fr-…) est généré automatiquement.</p>
        @endunless

        <x-input name="name" label="Nom fournisseur" :value="$supplier->name" required />
        <x-input name="city" label="Ville fournisseur" :value="$supplier->city" required />

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Enregistrer</button>
            <a href="{{ route('admin.suppliers.index') }}" class="btn btn-ghost">Annuler</a>
        </div>
    </form>
@endsection
