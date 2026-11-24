@extends('layouts.app')

@section('title', $product->designation)

@section('content')
    <nav class="breadcrumb" aria-label="Fil d’Ariane">
        <a href="{{ route('catalog.index') }}">Catalogue</a> /
        <a href="{{ route('catalog.index', ['family' => $product->family]) }}">{{ $product->familyLabel() }}</a> /
        <span aria-current="page">{{ $product->designation }}</span>
    </nav>

    @unless ($product->isPurchasable())
        <div class="alert alert-error admin-preview-banner" role="status">
            <x-icon name="alert" />
            <span>Aperçu administrateur : ce produit est {{ $product->isArchived() ? 'archivé' : 'masqué' }} et n’est pas visible pour les clients.
                <a href="{{ route('admin.products.edit', $product) }}">Modifier le produit</a></span>
        </div>
    @endunless

    <div class="product-detail">
        <x-product-image :product="$product" size="lg" />

        <div class="product-info">
            <p class="eyebrow">{{ $product->familyLabel() }}</p>
            <h1>{{ $product->designation }}</h1>
            <p class="product-ref">Réf. <span class="code">{{ $product->code }}</span> · Fournisseur : {{ $product->supplier->name }}</p>

            {{-- Everything needed to buy, grouped in one box --}}
            <section class="buy-box" aria-label="Acheter">
                <div class="buy-box-price">
                    <div>
                        <span class="price price-lg">{{ money($product->unit_price) }}</span>
                        <small>Prix unitaire TTC, non négociable</small>
                    </div>
                    <x-stock-badge :product="$product" />
                </div>

                <x-add-to-cart :product="$product" stepper />

                <div class="buy-extra">
                    <span><x-icon name="truck" /> À la livraison ou à 30 jours</span>
                    @if (auth()->user()?->isCustomer())
                        <x-favorite-button :product="$product" :active="$isFavorite" inline />
                    @elseif (! auth()->check())
                        <a href="{{ route('login') }}" class="small-link"><x-icon name="heart" /> Se connecter pour garder en favori</a>
                    @endif
                </div>
            </section>

            @if ($product->description)
                <h2>Description</h2>
                <p class="description">{{ $product->description }}</p>
            @endif

            <dl class="details">
                <dt>Référence</dt><dd class="code">{{ $product->code }}</dd>
                <dt>Famille</dt><dd>{{ $product->familyLabel() }} ({{ $product->family }})</dd>
                <dt>Fournisseur</dt><dd>{{ $product->supplier->name }}, {{ $product->supplier->city }}</dd>
            </dl>
        </div>
    </div>

    @if ($related->isNotEmpty())
        <section class="related">
            <div class="section-head">
                <h2>Dans la même famille</h2>
                <a href="{{ route('catalog.index', ['family' => $product->family]) }}" class="small-link">Tout voir <x-icon name="arrow-right" /></a>
            </div>
            @php($favoriteIds = auth()->user()?->favoriteProducts()->pluck('products.id')->all() ?? [])
            <div class="product-grid">
                @foreach ($related as $item)
                    @include('catalog._card', ['product' => $item])
                @endforeach
            </div>
        </section>
    @endif
@endsection
