@extends('layouts.app')

@section('title', 'Connexion')

@section('content')
    <div class="auth-box card">
        <h1>Connexion</h1>
        <form action="{{ route('login') }}" method="post" class="stack">
            @csrf
            <x-input name="email" label="E-mail" type="email" required autofocus autocomplete="email" />
            <x-password-field name="password" label="Mot de passe" autocomplete="current-password" />
            <label class="checkbox">
                <input type="checkbox" name="remember" value="1"> Se souvenir de moi
            </label>
            <button type="submit" class="btn btn-primary btn-block">Se connecter</button>
        </form>
        <p class="muted center">Pas encore de compte ? <a href="{{ route('register') }}">Créer un compte</a></p>
    </div>
@endsection
