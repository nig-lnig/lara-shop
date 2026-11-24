@extends('layouts.app')

@section('title', 'Mon profil')

@section('content')
    <h1>Mon compte</h1>
    @include('partials.account-nav')

    <div class="profile-layout">
        <form action="{{ route('profile.update') }}" method="post" class="card stack">
            @csrf
            @method('PUT')
            <h2>Coordonnées et adresse</h2>
            <x-input name="name" label="Nom" :value="$user->name" required autocomplete="name" />
            <x-input name="email" label="E-mail" type="email" :value="$user->email" required autocomplete="email" />
            <x-input name="street" label="Rue" :value="$client->street" required autocomplete="street-address" />
            <div class="row-2">
                <x-input name="postal_code" label="Code postal" :value="$client->postal_code" required autocomplete="postal-code" inputmode="numeric" />
                <x-input name="city" label="Ville" :value="$client->city" required autocomplete="address-level2" />
            </div>
            <button type="submit" class="btn btn-primary">Enregistrer</button>
        </form>

        <div class="stack">
            <section class="card">
                <h2>Mon compte client</h2>
                <dl class="details">
                    <dt>Code client</dt><dd>{{ $client->code }}</dd>
                    <dt>Type de client</dt><dd>{{ $client->typeLabel() }} ({{ $client->type }})</dd>
                    <dt>Remise spéciale</dt><dd>{{ $client->special_discount }} %</dd>
                </dl>
                <p class="muted small">Le type et la remise sont attribués par l’administrateur.</p>
            </section>

            <form action="{{ route('profile.password') }}" method="post" class="card stack">
                @csrf
                @method('PUT')
                <h2>Mot de passe</h2>
                <x-password-field name="current_password" label="Mot de passe actuel" autocomplete="current-password" />
                <x-password-field name="password" label="Nouveau mot de passe" checklist />
                <x-password-field name="password_confirmation" label="Confirmation du nouveau mot de passe" />
                <button type="submit" class="btn btn-outline">Changer le mot de passe</button>
            </form>
        </div>
    </div>
@endsection
