{{-- Storefront layout: every customer page starts with @extends('layouts.app') --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Boutique') · {{ config('shop.name') }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="preload" href="{{ asset('fonts/inter-latin-wght-normal.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<a class="skip-link" href="#contenu">Aller au contenu</a>

@php
    $user = auth()->user();
    $currentFamily = request()->routeIs('catalog.index') && is_string(request('family')) ? request('family') : null;
@endphp

<div class="topbar">
    <div class="container">
        <span><x-icon name="truck" /> Livraison partout au Maroc</span>
        <span class="hide-sm">Paiement à la livraison ou à 30 jours</span>
        <span class="hide-sm">Prix fixes, non négociables</span>
    </div>
</div>

<header class="site-header">
    <div class="container header-main">
        <a href="{{ route('catalog.index') }}" class="brand" aria-label="{{ config('shop.name') }}, accueil">
            <span class="brand-mark" aria-hidden="true">L</span> ara-Shop
        </a>

        <form action="{{ route('catalog.index') }}" method="get" class="search" role="search">
            <x-icon name="search" />
            <label for="header-q" class="sr-only">Rechercher un produit</label>
            <input id="header-q" type="search" name="q" value="{{ is_string(request('q')) ? request('q') : '' }}"
                   placeholder="Rechercher un article ou un code Pr-…" autocomplete="off">
            <button type="submit" class="btn btn-primary btn-sm">Rechercher</button>
        </form>

        <div class="header-actions">
            @auth
                @if ($user->isCustomer())
                    <a href="{{ route('favorites.index') }}" class="header-link" @if (request()->routeIs('favorites.*')) aria-current="page" @endif>
                        <x-icon name="heart" /><span class="label-text">Favoris</span>
                    </a>
                @endif

                {{-- Account menu (popover). Without JavaScript, the links stay reachable from each page. --}}
                <div class="menu-wrap" data-menu>
                    <button type="button" class="header-link" aria-expanded="false" aria-controls="account-menu" data-menu-trigger>
                        <x-icon name="user" /><span class="label-text">Mon compte</span><x-icon name="chevron-down" class="icon chev" />
                    </button>
                    <div id="account-menu" class="popover" data-menu-panel>
                        <div class="popover-head">
                            <strong>{{ $user->name }}</strong>
                            <span class="muted small">{{ $user->isAdmin() ? 'Administrateur' : 'Client '.$user->client?->code }}</span>
                        </div>
                        @if ($user->isCustomer())
                            <a href="{{ route('orders.index') }}"><x-icon name="box" /> Mes commandes</a>
                            <a href="{{ route('my-data.index') }}"><x-icon name="download" /> Mes données</a>
                            <a href="{{ route('favorites.index') }}"><x-icon name="heart" /> Mes favoris</a>
                            <a href="{{ route('profile.edit') }}"><x-icon name="user" /> Profil et adresse</a>
                        @endif
                        @if ($user->isAdmin())
                            <a href="{{ route('admin.dashboard') }}"><x-icon name="grid" /> Administration</a>
                        @endif
                        <form action="{{ route('logout') }}" method="post">
                            @csrf
                            <button type="submit" class="popover-item"><x-icon name="logout" /> Déconnexion</button>
                        </form>
                    </div>
                </div>
            @else
                <a href="{{ route('login') }}" class="header-link"><x-icon name="user" /><span class="label-text">Connexion</span></a>
            @endauth

            <a href="{{ route('cart.index') }}" class="header-link cart-link" aria-label="Panier, {{ $cartCount }} article(s)" data-cart-link>
                <x-icon name="bag" /><span class="label-text">Panier</span>
                <span class="cart-count" data-cart-count data-empty="{{ $cartCount === 0 ? 'true' : 'false' }}">{{ $cartCount }}</span>
            </a>
        </div>
    </div>

    {{-- The four product families from the subject (PA, BU, FO, LI) --}}
    <nav class="family-nav" aria-label="Familles de produits">
        <div class="container">
            <ul>
                <li><a href="{{ route('catalog.index') }}" @if (request()->routeIs('catalog.index') && ! $currentFamily) aria-current="page" @endif>Tout le catalogue</a></li>
                @foreach (App\Models\Product::FAMILIES as $code => $label)
                    <li>
                        <a href="{{ route('catalog.index', ['family' => $code]) }}" @if ($currentFamily === $code) aria-current="page" @endif>
                            <span class="family-dot dot-{{ strtolower($code) }}" aria-hidden="true"></span>{{ $label }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </nav>
</header>

<main id="contenu" class="container main">
    @include('partials.flash')
    @yield('content')
</main>

<footer class="site-footer">
    <div class="container footer-grid">
        <div>
            <p class="brand"><span class="brand-mark" aria-hidden="true">L</span> ara-Shop</p>
            <p>Papeterie, bureautique, fournitures et librairie pour l’école, le bureau et la maison.</p>
            <p class="muted small">Projet pédagogique Laravel · EST Safi · LP-ISIR 2026/2027</p>
        </div>
        <div>
            <h2>Familles</h2>
            <ul>
                @foreach (App\Models\Product::FAMILIES as $code => $label)
                    <li><a href="{{ route('catalog.index', ['family' => $code]) }}">{{ $label }}</a></li>
                @endforeach
            </ul>
        </div>
        <div>
            <h2>Mon compte</h2>
            <ul>
                <li><a href="{{ route('orders.index') }}">Mes commandes</a></li>
                <li><a href="{{ route('my-data.index') }}">Mes données</a></li>
                <li><a href="{{ route('cart.index') }}">Panier</a></li>
            </ul>
        </div>
    </div>
</footer>

{{-- Dark layer behind the mobile filter sheet --}}
<div class="panel-backdrop" data-panel-backdrop></div>

<script src="{{ asset('js/app.js') }}" defer></script>
</body>
</html>
