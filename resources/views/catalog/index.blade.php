@extends('layouts.app')

@section('title', isset($filters['family']) ? App\Models\Product::FAMILIES[$filters['family']] : 'Catalogue')

@php
    // Active filters, each with the URL that removes it (shown as "chips").
    $chips = [];
    if (isset($filters['q'])) $chips['q'] = 'Recherche : « '.$filters['q'].' »';
    if (isset($filters['family'])) $chips['family'] = App\Models\Product::FAMILIES[$filters['family']];
    if (isset($filters['min_price'])) $chips['min_price'] = 'Dès '.money($filters['min_price']);
    if (isset($filters['max_price'])) $chips['max_price'] = 'Jusqu’à '.money($filters['max_price']);
    if (isset($filters['in_stock'])) $chips['in_stock'] = 'En stock uniquement';
@endphp

@section('content')
    @php($showHero = $filters === [] && $products->currentPage() === 1)
    @if ($showHero)
        {{-- Hero: only on the home page of the shop (no filter, first page) --}}
        <section class="hero" aria-labelledby="hero-title">
            <div class="hero-copy">
                <p class="eyebrow">Papeterie · Bureau · Librairie</p>
                <h1 id="hero-title">Tout pour écrire, ranger et apprendre</h1>
                <p>{{ $familyCounts->sum() }} articles à prix fixes, à payer à la livraison ou à 30 jours.</p>
                <div class="hero-actions">
                    <a href="#catalogue" class="btn btn-primary btn-lg">Voir le catalogue</a>
                </div>
            </div>
            <div class="hero-media">
                <img src="{{ asset('images/hero-lara-shop.svg') }}" width="1000" height="600"
                     alt="Cahier à spirale, guide de comptabilité, atlas, calculatrice, crayons et plante">
            </div>
        </section>
    @endif

    <div class="catalog-head" id="catalogue">
        <div>
            {{-- h1 when there is no hero, h2 under the hero (one h1 per page) --}}
            <{{ $showHero ? 'h2' : 'h1' }} class="catalog-title">{{ isset($filters['family']) ? App\Models\Product::FAMILIES[$filters['family']] : 'Le catalogue' }}</{{ $showHero ? 'h2' : 'h1' }}>
            <p>Fournitures pour l’école, le bureau et la maison. Prix unitaires fixes.</p>
        </div>
    </div>

    <div class="catalog-layout">
        {{--
            Filters. One GET form: the filters stay in the URL (shareable, paginated).
            Desktop: sidebar. Mobile: bottom sheet opened by the "Filtres" button.
        --}}
        <aside id="filter-panel" class="filter-panel" aria-labelledby="filter-title" data-panel>
            <form method="get" action="{{ route('catalog.index') }}">
                <div class="filter-panel-head">
                    <h2 id="filter-title">Filtres</h2>
                    <button type="button" class="icon-btn" data-panel-close aria-label="Fermer les filtres"><x-icon name="x" /></button>
                </div>

                @isset($filters['sort']) <input type="hidden" name="sort" value="{{ $filters['sort'] }}"> @endisset

                <div class="filter-group is-first">
                    <label for="filter-q" class="label">Recherche</label>
                    <input id="filter-q" type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Nom ou code produit">
                </div>

                <fieldset class="filter-group">
                    <legend>Famille</legend>
                    <div class="radio-list">
                        <label class="radio-row">
                            <input type="radio" name="family" value="" @checked(! isset($filters['family']))> Toutes
                            <span class="count">{{ $familyCounts->sum() }}</span>
                        </label>
                        @foreach (App\Models\Product::FAMILIES as $code => $label)
                            <label class="radio-row">
                                <input type="radio" name="family" value="{{ $code }}" @checked(($filters['family'] ?? '') === $code)>
                                <span class="family-dot dot-{{ strtolower($code) }}" aria-hidden="true"></span> {{ $label }}
                                <span class="count">{{ $familyCounts[$code] ?? 0 }}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <fieldset class="filter-group">
                    <legend>Prix ({{ config('shop.currency') }})</legend>
                    <div class="price-range">
                        <label for="min_price" class="sr-only">Prix minimum</label>
                        <input id="min_price" type="number" name="min_price" min="0" step="0.01" inputmode="decimal" value="{{ $filters['min_price'] ?? '' }}" placeholder="Min">
                        <span class="muted" aria-hidden="true">–</span>
                        <label for="max_price" class="sr-only">Prix maximum</label>
                        <input id="max_price" type="number" name="max_price" min="0" step="0.01" inputmode="decimal" value="{{ $filters['max_price'] ?? '' }}" placeholder="Max">
                    </div>
                </fieldset>

                <div class="filter-group">
                    <label class="checkbox"><input type="checkbox" name="in_stock" value="1" @checked(isset($filters['in_stock']))> En stock uniquement</label>
                </div>

                <div class="filter-panel-foot">
                    <a href="{{ route('catalog.index') }}" class="btn btn-outline">Réinitialiser</a>
                    <button type="submit" class="btn btn-primary">Appliquer</button>
                </div>
            </form>
        </aside>

        <section aria-label="Produits">
            <div class="results-bar">
                <button type="button" class="btn btn-outline btn-sm filter-toggle" aria-controls="filter-panel" aria-expanded="false" data-panel-open="filter-panel">
                    <x-icon name="filter" /> Filtres
                    @if ($chips) <span class="filter-badge">{{ count($chips) }}</span> @endif
                </button>
                <p class="results-count" role="status">{{ $products->total() }} article{{ $products->total() > 1 ? 's' : '' }}</p>

                <form method="get" action="{{ route('catalog.index') }}" class="sort-form">
                    @foreach (collect($filters)->except('sort') as $key => $value)
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endforeach
                    <label for="sort">Trier par</label>
                    <select id="sort" name="sort" data-auto-submit>
                        @foreach (App\Models\Product::SORTS as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['sort'] ?? 'name') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <noscript><button type="submit" class="btn btn-outline btn-sm">Trier</button></noscript>
                </form>
            </div>

            @if ($chips)
                <div class="chips" aria-label="Filtres actifs">
                    @foreach ($chips as $key => $label)
                        <a class="chip" href="{{ request()->fullUrlWithoutQuery([$key, 'page']) }}">
                            {{ $label }} <x-icon name="x" /><span class="sr-only">(retirer ce filtre)</span>
                        </a>
                    @endforeach
                    <a class="clear-all" href="{{ route('catalog.index') }}">Tout effacer</a>
                </div>
            @endif

            @if ($products->isEmpty())
                <div class="empty-state card">
                    <x-icon name="search" />
                    <h2>Aucun article trouvé</h2>
                    <p>Essayez un autre mot-clé ou retirez un filtre.</p>
                    <a href="{{ route('catalog.index') }}" class="btn btn-outline">Voir tout le catalogue</a>
                </div>
            @else
                <div class="product-grid">
                    @foreach ($products as $product)
                        @include('catalog._card')
                    @endforeach
                </div>
                {{ $products->links() }}
            @endif
        </section>
    </div>
@endsection
