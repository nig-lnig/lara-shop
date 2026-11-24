@extends('layouts.app')

@section('title', 'Créer un compte')

@section('content')
    <div class="auth-box auth-box-wide card">
        <h1>Créer un compte client</h1>
        <p class="muted">Votre code client (Cli-…) sera généré automatiquement.</p>
        <form action="{{ route('register') }}" method="post" class="stack">
            @csrf
            <x-input name="name" label="Nom complet ou raison sociale" required autofocus autocomplete="name" />
            <x-input name="email" label="E-mail" type="email" required autocomplete="email" inputmode="email" maxlength="190"
                     help="Exemple : prenom.nom@domaine.ma" />
            <x-password-field name="password" label="Mot de passe" checklist />
            <x-password-field name="password_confirmation" label="Confirmation du mot de passe" />
            <x-input name="street" label="Rue" required autocomplete="street-address" />
            <div class="row-2">
                <x-input name="postal_code" label="Code postal" required autocomplete="postal-code" inputmode="numeric" />
                <x-input name="city" label="Ville" required autocomplete="address-level2" />
            </div>
            <button type="submit" class="btn btn-primary btn-block">Créer mon compte</button>
        </form>
        <p class="muted center">Déjà inscrit ? <a href="{{ route('login') }}">Se connecter</a></p>
    </div>
@endsection
