{{-- Simple status tracker: En attente → En préparation → Expédiée → Livrée (or Annulée) --}}
@php
    $steps = ['pending', 'preparing', 'shipped', 'delivered'];
    $currentIndex = array_search($order->status, $steps);
@endphp

@if ($order->status === 'cancelled')
    <p class="alert alert-error">Cette commande a été annulée.</p>
@else
    <ol class="tracker" aria-label="Suivi de la commande">
        @foreach ($steps as $i => $step)
            <li @class(['done' => $i < $currentIndex, 'current' => $i === $currentIndex])
                @if ($i === $currentIndex) aria-current="step" @endif>
                <span class="dot" aria-hidden="true"></span>
                {{ App\Models\Order::STATUSES[$step] }}
            </li>
        @endforeach
    </ol>
@endif
