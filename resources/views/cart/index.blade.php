@extends('layouts.app')

@section('title', 'Panier')

@section('content')
    <div class="page-head">
        <div>
            <h1>Mon panier</h1>
            @if ($cart['lines'] !== [])
                <p class="muted">{{ count($cart['lines']) }} article(s) différent(s)</p>
            @endif
        </div>
    </div>

    @if ($cart['lines'] === [])
        <div class="empty-state card">
            <x-icon name="bag" />
            <h2>Votre panier est vide</h2>
            <p>Parcourez le catalogue et ajoutez vos fournitures.</p>
            <a href="{{ route('catalog.index') }}" class="btn btn-primary">Voir le catalogue</a>
        </div>
    @else
        <div class="cart-layout">
            <section class="card" aria-label="Articles du panier">
                <ul class="cart-lines">
                    @foreach ($cart['lines'] as $line)
                        @php($product = $line['product'])
                        <li class="cart-line">
                            <x-product-image :product="$product" size="sm" />
                            <div class="cart-line-info">
                                <a href="{{ route('catalog.show', $product) }}" class="cart-line-title">{{ $product->designation }}</a>
                                <span class="muted small"><span class="code">{{ $product->code }}</span> · {{ money($line['unit_price']) }} l’unité</span>
                                @unless ($line['enough_stock'])
                                    <span class="field-error"><x-icon name="alert" /> Stock insuffisant : {{ $product->stock }} disponible(s).</span>
                                @endunless
                            </div>
                            <form action="{{ route('cart.update', $product) }}" method="post" class="qty-form">
                                @csrf
                                @method('PATCH')
                                <div class="stepper" data-stepper>
                                    <button type="button" data-step="-1" aria-label="Diminuer la quantité de {{ $product->designation }}"><x-icon name="minus" /></button>
                                    <label for="qty-{{ $product->id }}" class="sr-only">Quantité de {{ $product->designation }}</label>
                                    <input id="qty-{{ $product->id }}" type="number" name="quantity" value="{{ $line['quantity'] }}"
                                           min="1" max="{{ config('shop.max_quantity') }}" inputmode="numeric" data-auto-submit>
                                    <button type="button" data-step="1" aria-label="Augmenter la quantité de {{ $product->designation }}"><x-icon name="plus" /></button>
                                </div>
                                <button type="submit" class="btn btn-ghost btn-sm qty-submit">Mettre à jour</button>
                            </form>
                            <strong class="cart-line-total">{{ money($line['line_total']) }}</strong>
                            <form action="{{ route('cart.destroy', $product) }}" method="post">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="icon-btn" title="Retirer du panier">
                                    <x-icon name="x" /><span class="sr-only">Retirer {{ $product->designation }}</span>
                                </button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            </section>

            <aside class="card summary" aria-label="Récapitulatif">
                <h2>Récapitulatif</h2>
                @include('partials.totals', ['totals' => $cart])

                <div style="margin-top: 1.25rem">
                    @guest
                        <a href="{{ route('login') }}" class="btn btn-primary btn-block btn-lg">Se connecter pour commander</a>
                        <p class="summary-note">Votre panier est conservé. Votre remise client s’applique une fois connecté.</p>
                    @else
                        @if (auth()->user()->isCustomer())
                            @if ($cart['has_stock_problem'])
                                <p class="field-error"><x-icon name="alert" /> Ajustez les quantités signalées avant de commander.</p>
                            @endif
                            <a href="{{ route('checkout.create') }}" class="btn btn-primary btn-block btn-lg"
                               @if ($cart['has_stock_problem']) aria-disabled="true" tabindex="-1" @endif>Passer la commande</a>
                        @else
                            <p class="muted">Les comptes administrateurs ne peuvent pas commander.</p>
                        @endif
                    @endguest
                    <a href="{{ route('catalog.index') }}" class="btn btn-ghost btn-block">Continuer mes achats</a>
                </div>
            </aside>
        </div>
    @endif
@endsection
