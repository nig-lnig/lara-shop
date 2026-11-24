@extends('layouts.app')

@section('title', 'Mes données')

@section('content')
    <h1>Mon compte</h1>
    @include('partials.account-nav')

    <div class="page-head">
        <div>
            <h2 style="margin: 0">Mes données</h2>
            <p class="muted">
                Tout ce que {{ config('shop.name') }} conserve sur votre compte client <strong class="code">{{ $client->code }}</strong>.
                Formats CSV, Excel ou PDF. Votre mot de passe n’est jamais exporté.
            </p>
        </div>
    </div>

    <div class="info-grid">
        <div class="card stat"><span class="stat-value">{{ $ordersCount }}</span><span class="stat-label">commande(s)</span></div>
        <div class="card stat"><span class="stat-value">{{ $favoritesCount }}</span><span class="stat-label">favori(s)</span></div>
        <div class="card stat"><span class="stat-value">{{ $client->special_discount }} %</span><span class="stat-label">remise spéciale</span></div>
    </div>

    <section class="card bundle-card spaced">
        <div>
            <h2>Toutes mes données</h2>
            <p>Profil, commandes, lignes de commande et favoris dans un seul fichier.</p>
        </div>
        <div class="actions">
            <a href="{{ route('my-data.report') }}" class="btn"><x-icon name="download" /> Rapport PDF</a>
            <a href="{{ route('my-data.workbook') }}" class="btn"><x-icon name="download" /> Classeur Excel</a>
            <a href="{{ route('my-data.bundle') }}" class="btn"><x-icon name="download" /> Archive CSV (ZIP)</a>
        </div>
    </section>

    <section class="card">
        <h2>Fichiers séparés</h2>
        <ul class="download-list">
            @foreach ($datasets as $key => [$baseName, $label])
                <li>
                    <div>
                        <strong>{{ $label }}</strong>
                        <span class="file">{{ $baseName }}.csv · .xlsx · .pdf</span>
                    </div>
                    <x-export-formats route="my-data.download" :dataset="$key" />
                </li>
            @endforeach
        </ul>
    </section>

    <section class="card">
        <h2>Récapitulatifs imprimables</h2>
        <p class="muted">Chaque commande se télécharge en PDF depuis sa page de détail.</p>
        <a href="{{ route('orders.index') }}" class="btn btn-outline">Voir mes commandes</a>
    </section>
@endsection
