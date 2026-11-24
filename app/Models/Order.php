<?php

namespace App\Models;

use App\Models\Concerns\HasBusinessCode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasBusinessCode, HasFactory;

    protected string $codePrefix = 'Com';

    protected string $codeColumn = 'number';

    /** Condition = payment terms (PDF page 6). */
    public const PAYMENT_TERMS = [
        'LIV' => 'À la livraison',
        'T30' => 'À 30 jours',
    ];

    /** Fulfillment status (separate from the payment terms). */
    public const STATUSES = [
        'pending' => 'En attente',
        'preparing' => 'En préparation',
        'shipped' => 'Expédiée',
        'delivered' => 'Livrée',
        'cancelled' => 'Annulée',
    ];

    /** Which status can follow which one. Delivered and cancelled are final. */
    public const NEXT_STATUSES = [
        'pending' => ['preparing', 'cancelled'],
        'preparing' => ['shipped', 'cancelled'],
        'shipped' => ['delivered'],
        'delivered' => [],
        'cancelled' => [],
    ];

    protected $fillable = [
        'order_date', 'payment_terms', 'client_id', 'status',
        'discount_rate', 'subtotal', 'discount_amount', 'total', 'checkout_token',
    ];

    protected function casts(): array
    {
        return [
            'order_date' => 'datetime',
            'discount_rate' => 'integer',
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'stock_restored_at' => 'datetime',
        ];
    }

    // ---- Relations -------------------------------------------------------

    /** Rule 2: an order is placed by exactly one client. */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /** Rule 3: an order contains one or more products (order lines). */
    public function lines(): HasMany
    {
        return $this->hasMany(OrderLine::class);
    }

    // ---- Helpers ---------------------------------------------------------

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function paymentTermsLabel(): string
    {
        return self::PAYMENT_TERMS[$this->payment_terms] ?? $this->payment_terms;
    }

    /** @return array<string, string> statuses the admin may choose next */
    public function nextStatuses(): array
    {
        return array_intersect_key(self::STATUSES, array_flip(self::NEXT_STATUSES[$this->status] ?? []));
    }

    public function canBeCancelledByCustomer(): bool
    {
        return $this->status === 'pending';
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['q'] ?? null, function ($q, $search) {
                $q->where(fn ($w) => $w->where('number', 'like', "%{$search}%")
                    ->orWhereHas('client', fn ($c) => $c->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")));
            })
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['payment_terms'] ?? null, fn ($q, $terms) => $q->where('payment_terms', $terms))
            ->when($filters['from'] ?? null, fn ($q, $from) => $q->whereDate('order_date', '>=', $from))
            ->when($filters['to'] ?? null, fn ($q, $to) => $q->whereDate('order_date', '<=', $to));
    }
}
