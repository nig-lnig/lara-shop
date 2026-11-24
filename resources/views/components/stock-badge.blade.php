{{-- Stock visibility for customers: dot + text. Usage: <x-stock-badge :product="$product" /> --}}
@props(['product'])

@if ($product->stock === 0)
    <span class="stock stock-out">Rupture de stock</span>
@elseif ($product->isLowStock())
    <span class="stock stock-low">Plus que {{ $product->stock }} en stock</span>
@else
    <span class="stock stock-ok">En stock</span>
@endif
