@extends('layouts.app')

@section('title', 'Validation de la commande')

@section('content')
    <a href="{{ route('cart.index') }}" class="small-link"><x-icon name="arrow-left" /> Retour au panier</a>
    <div class="page-head">
        <h1>Vérifier et confirmer</h1>
    </div>

    <div class="cart-layout">
        <form id="checkout-form" action="{{ route('checkout.store') }}" method="post" class="checkout-steps" data-once novalidate>
            @csrf
            {{-- Unique token for THIS form: submitting it twice creates only one order. --}}
            <input type="hidden" name="checkout_token" value="{{ $checkoutToken }}">

            <section class="card step" aria-labelledby="step-items">
                <div class="step-title">
                    <h2 id="step-items">Vos articles</h2>
                    <a href="{{ route('cart.index') }}">Modifier</a>
                </div>
                <ul class="review-lines">
                    @foreach ($cart['lines'] as $line)
                        <li class="review-line">
                            <x-product-image :product="$line['product']" size="xs" />
                            <div>
                                <div>{{ $line['product']->designation }}</div>
                                <div class="meta">{{ $line['quantity'] }} × {{ money($line['unit_price']) }}</div>
                            </div>
                            <strong>{{ money($line['line_total']) }}</strong>
                        </li>
                    @endforeach
                </ul>
            </section>

            <section class="card step" aria-labelledby="step-address">
                <div class="step-title">
                    <h2 id="step-address">Adresse de livraison</h2>
                    <a href="{{ route('profile.edit') }}">Modifier</a>
                </div>
                <address class="address">
                    <strong>{{ $client->name }}</strong> <span class="muted small">({{ $client->code }})</span><br>
                    {{ $client->street }}<br>
                    {{ $client->postal_code }} {{ $client->city }}
                </address>
            </section>

            <section class="card step" aria-labelledby="step-payment">
                <div class="step-title"><h2 id="step-payment">Condition de paiement</h2></div>
                <fieldset class="choice-group" @error('payment_terms') aria-describedby="payment_terms-error" @enderror>
                    <legend class="sr-only">Choisissez une condition de paiement</legend>
                    @foreach (App\Models\Order::PAYMENT_TERMS as $code => $label)
                        <label class="choice">
                            <input type="radio" name="payment_terms" value="{{ $code }}" @if ($loop->first) id="payment_terms" @endif
                                   @checked(old('payment_terms') === $code) required>
                            <span>
                                <strong>{{ $label }}</strong>
                                <span class="code">{{ $code }}</span>
                                <small>{{ $code === 'LIV' ? 'Vous payez à la réception de la commande.' : 'Facture payable sous 30 jours.' }}</small>
                            </span>
                        </label>
                    @endforeach
                    @error('payment_terms')
                        <p id="payment_terms-error" class="field-error choice-error"><x-icon name="alert" /> {{ $message }}</p>
                    @enderror
                </fieldset>
                <p class="muted small" style="margin: .9rem 0 0">Aucun paiement en ligne : la condition choisie est enregistrée avec la commande.</p>
            </section>
        </form>

        <aside class="card summary" aria-label="Total de la commande">
            <h2>Total</h2>
            @include('partials.totals', ['totals' => $cart])
            @if ($cart['discount_rate'] > 0)
                <p class="summary-note">Votre remise client de {{ $cart['discount_rate'] }} % est appliquée au sous-total.</p>
            @endif
            {{-- form="checkout-form": this button submits the form on the left --}}
            <button type="submit" form="checkout-form" class="btn btn-primary btn-lg btn-block" style="margin-top: 1.25rem"
                    data-loading-text="Commande en cours…">
                <span class="btn-label">Confirmer la commande</span>
            </button>
            <p class="summary-note">Les prix et la remise sont recalculés et enregistrés par le serveur au moment de la confirmation.</p>
        </aside>
    </div>
@endsection
