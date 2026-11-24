{{-- One product card. Needs $product and $favoriteIds (array of ids). --}}
<article class="product-card">
    <a href="{{ route('catalog.show', $product) }}" class="product-card-link">
        <x-product-image :product="$product" />
        <div class="product-card-body">
            <p class="eyebrow">{{ $product->familyLabel() }}</p>
            <h3 class="product-title">{{ $product->designation }}</h3>
            <div class="product-price-row">
                <span class="price">{{ money($product->unit_price) }}</span>
            </div>
            <x-stock-badge :product="$product" />
        </div>
    </a>

    @if (auth()->user()?->isCustomer())
        <x-favorite-button :product="$product" :active="in_array($product->id, $favoriteIds)" />
    @endif

    <div class="product-card-actions">
        <x-add-to-cart :product="$product" />
    </div>
</article>
