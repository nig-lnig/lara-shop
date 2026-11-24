{{--
    "Ajouter au panier" form, in three sizes:
      <x-add-to-cart :product="$product" />           full-width button
      <x-add-to-cart :product="$product" stepper />   quantity [-] [1] [+] + button (product page)
      <x-add-to-cart :product="$product" compact />   round violet button (product cards)

    Without JavaScript: a normal POST, the page reloads with a message.
    With JavaScript (public/js/app.js, "data-add-to-cart"): sent with fetch(),
    the button confirms, the header count updates, and the message appears
    right next to the button (data-feedback).
--}}
@props(['product', 'stepper' => false, 'compact' => false])

@php($max = min($product->stock, config('shop.max_quantity')))

<form action="{{ route('cart.store') }}" method="post" data-add-to-cart data-cart-url="{{ route('cart.index') }}"
      @if ($compact) data-compact class="add-compact" @endif {{ $attributes }}>
    @csrf
    <input type="hidden" name="product_id" value="{{ $product->id }}">

    @if ($compact)
        <button type="submit" class="add-btn" @disabled($product->stock === 0)
                aria-label="{{ $product->stock === 0 ? 'Indisponible' : 'Ajouter « '.$product->designation.' » au panier' }}"
                title="{{ $product->stock === 0 ? 'Indisponible' : 'Ajouter au panier' }}">
            <span class="btn-label" data-label><x-icon name="{{ $product->stock === 0 ? 'x' : 'plus' }}" /></span>
        </button>
        <span class="add-bubble" data-feedback role="status" aria-live="polite"></span>
    @else
        <div @class(['buy-row' => $stepper])>
            @if ($stepper && $product->stock > 0)
                <div class="stepper" data-stepper>
                    <button type="button" data-step="-1" aria-label="Diminuer la quantité"><x-icon name="minus" /></button>
                    <label for="quantity-{{ $product->id }}" class="sr-only">Quantité</label>
                    <input id="quantity-{{ $product->id }}" type="number" name="quantity" value="1" min="1" max="{{ $max }}" inputmode="numeric">
                    <button type="button" data-step="1" aria-label="Augmenter la quantité"><x-icon name="plus" /></button>
                </div>
            @endif

            <button type="submit" class="btn btn-primary {{ $stepper ? '' : 'btn-block' }}" @disabled($product->stock === 0)>
                <span class="btn-label" data-label>
                    @if ($product->stock === 0)
                        Indisponible
                    @else
                        <x-icon name="bag" /> Ajouter au panier
                    @endif
                </span>
            </button>
        </div>

        <p class="inline-feedback" data-feedback role="status" aria-live="polite"></p>
    @endif
</form>
