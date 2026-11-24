{{-- Coloured badge for an order status. Usage: <x-status-badge :order="$order" /> --}}
@props(['order'])

<span class="status status-{{ $order->status }}">{{ $order->statusLabel() }}</span>
