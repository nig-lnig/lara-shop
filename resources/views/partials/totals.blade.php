{{-- Subtotal / discount / total. $totals has subtotal, discount_rate, discount_amount, total (cart array or Order). --}}
<dl class="totals">
    <dt>Sous-total</dt>
    <dd>{{ money($totals['subtotal']) }}</dd>

    <dt>Remise client ({{ $totals['discount_rate'] }} %)</dt>
    <dd @class(['discount' => $totals['discount_amount'] > 0])>{{ $totals['discount_amount'] > 0 ? '− ' : '' }}{{ money($totals['discount_amount']) }}</dd>

    <dt class="total-label">Total</dt>
    <dd class="total-value">{{ money($totals['total']) }}</dd>
</dl>
