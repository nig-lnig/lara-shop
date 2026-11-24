{{-- Admin layout: compact sidebar + content. Used by every page in resources/views/admin --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Administration') · Admin {{ config('shop.name') }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="admin-body">
<a class="skip-link" href="#contenu">Aller au contenu</a>

@php
    // [route name, pattern for the active state, label, icon]
    $sections = [
        'Ventes' => [
            ['admin.dashboard', 'admin.dashboard', 'Tableau de bord', 'grid'],
            ['admin.orders.index', 'admin.orders.*', 'Commandes', 'box'],
        ],
        'Catalogue' => [
            ['admin.products.index', 'admin.products.*', 'Produits et stock', 'tag'],
            ['admin.suppliers.index', 'admin.suppliers.*', 'Fournisseurs', 'building'],
        ],
        'Relation client' => [
            ['admin.clients.index', 'admin.clients.*', 'Clients', 'users'],
        ],
        'Données' => [
            ['admin.exports.index', 'admin.exports.*', 'Exports', 'download'],
        ],
    ];
@endphp

<div class="admin-shell">
    <aside class="admin-sidebar">
        <div class="admin-sidebar-top">
            <a href="{{ route('admin.dashboard') }}" class="brand"><span class="brand-mark" aria-hidden="true">L</span> Admin</a>
            <button class="icon-btn nav-toggle" type="button" aria-expanded="false" aria-controls="admin-nav" data-nav-toggle>
                <x-icon name="grid" /><span class="sr-only">Menu d’administration</span>
            </button>
        </div>
        <nav id="admin-nav" class="admin-nav" aria-label="Administration">
            @foreach ($sections as $title => $links)
                <span class="nav-section">{{ $title }}</span>
                @foreach ($links as [$route, $pattern, $label, $icon])
                    <a href="{{ route($route) }}" @if (request()->routeIs($pattern)) aria-current="page" @endif>
                        <x-icon :name="$icon" /> {{ $label }}
                    </a>
                @endforeach
            @endforeach
            <span class="nav-section">{{ auth()->user()->name }}</span>
            <a href="{{ route('catalog.index') }}"><x-icon name="store" /> Voir la boutique</a>
            <form action="{{ route('logout') }}" method="post">
                @csrf
                <button type="submit" class="link-button"><x-icon name="logout" /> Déconnexion</button>
            </form>
        </nav>
    </aside>

    <main id="contenu" class="admin-main">
        @include('partials.flash')
        @yield('content')
    </main>
</div>

<script src="{{ asset('js/app.js') }}" defer></script>
</body>
</html>
