{{--
    Heart button to add / remove a favorite (customers only).
    Usage: <x-favorite-button :product="$product" :active="true" />          (round icon, on cards)
           <x-favorite-button :product="$product" :active="true" inline />   (text button, product page)
    Works as a normal form; JavaScript ("data-favorite") avoids the page reload.
--}}
@props(['product', 'active' => false, 'inline' => false])

<form action="{{ route('favorites.toggle', $product) }}" method="post" data-favorite {{ $attributes }}>
    @csrf
    <button type="submit" class="{{ $inline ? 'fav-inline' : 'icon-btn fav-btn' }}" aria-pressed="{{ $active ? 'true' : 'false' }}"
            data-label-on="Retirer des favoris" data-label-off="Ajouter aux favoris"
            @unless ($inline) aria-label="{{ $active ? 'Retirer des favoris' : 'Ajouter aux favoris' }}" title="{{ $active ? 'Retirer des favoris' : 'Ajouter aux favoris' }}" @endunless>
        <x-icon name="heart" />
        @if ($inline)<span data-text>{{ $active ? 'Retirer des favoris' : 'Ajouter aux favoris' }}</span>@endif
    </button>
</form>
