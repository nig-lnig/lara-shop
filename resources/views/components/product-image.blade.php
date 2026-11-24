{{--
    Product picture with a fallback.
    Usage: <x-product-image :product="$product" />  (size: xs, sm, md, lg)

    The coloured "family" tile is always drawn underneath. If there is no
    picture, or if it fails to load (onerror removes the <img>), the tile shows.
--}}
@props(['product', 'size' => 'md'])

<div {{ $attributes->class(['product-image', 'product-image-'.$size]) }}>
    <div class="image-fallback family-{{ strtolower((string) $product->family) }}" aria-hidden="true">
        <span class="fallback-code">{{ $product->family }}</span>
        <span class="fallback-label">{{ $product->familyLabel() }}</span>
    </div>
    @if ($product->imageUrl())
        <img src="{{ $product->imageUrl() }}" alt="{{ $product->designation }}" loading="lazy" onerror="this.remove()">
    @endif
</div>
