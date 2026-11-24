{{-- Tabs shared by the customer account pages --}}
<nav class="account-nav" aria-label="Mon compte">
    <a href="{{ route('orders.index') }}" @if (request()->routeIs('orders.*')) aria-current="page" @endif><x-icon name="box" /> Mes commandes</a>
    <a href="{{ route('my-data.index') }}" @if (request()->routeIs('my-data.*')) aria-current="page" @endif><x-icon name="download" /> Mes données</a>
    <a href="{{ route('favorites.index') }}" @if (request()->routeIs('favorites.*')) aria-current="page" @endif><x-icon name="heart" /> Favoris</a>
    <a href="{{ route('profile.edit') }}" @if (request()->routeIs('profile.*')) aria-current="page" @endif><x-icon name="user" /> Profil et adresse</a>
</nav>
