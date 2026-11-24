<?php

namespace App\Models;

use App\Models\Concerns\Archivable;
use App\Models\Concerns\HasBusinessCode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use Archivable, HasBusinessCode, HasFactory;

    protected string $codePrefix = 'Pr';

    /** Famille (PDF page 7). Code => French label. */
    public const FAMILIES = [
        'PA' => 'Papeterie',
        'BU' => 'Bureautique',
        'FO' => 'Fourniture',
        'LI' => 'Librairie',
    ];

    /** Default values of a new Product object (same as the database defaults). */
    protected $attributes = [
        'is_listed' => true,
    ];

    protected $fillable = [
        'designation', 'description', 'unit_price', 'family', 'supplier_id',
        'stock', 'low_stock_threshold', 'image_path', 'is_listed',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2', // always a string like "12.50"
            'stock' => 'integer',
            'low_stock_threshold' => 'integer',
            'is_listed' => 'boolean',
            'archived_at' => 'datetime',
        ];
    }

    // ---- Relations -------------------------------------------------------

    /** Rule 6: a product is supplied by exactly one supplier. */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /** Rule 4: a product can appear in many orders (through order lines). */
    public function orderLines(): HasMany
    {
        return $this->hasMany(OrderLine::class);
    }

    // ---- Helpers ---------------------------------------------------------

    public function familyLabel(): string
    {
        return self::FAMILIES[$this->family ?? ''] ?? (string) $this->family;
    }

    public function isLowStock(): bool
    {
        return $this->stock <= $this->low_stock_threshold;
    }

    /**
     * Public URL of the picture (storage/app/public/... is reachable at /storage/...
     * thanks to "php artisan storage:link").
     * asset() builds the URL from the address the visitor is really using, so the
     * picture works with php artisan serve, localhost, 127.0.0.1 or a WAMP folder.
     */
    public function imageUrl(): ?string
    {
        return $this->image_path ? asset('storage/'.$this->image_path) : null;
    }

    /** Can a customer see and order this product? */
    public function isPurchasable(): bool
    {
        return ! $this->isArchived() && $this->is_listed;
    }

    // ---- Query scopes ----------------------------------------------------

    /** Products customers can see and buy: not archived AND listed in the shop. */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->active()->where('is_listed', true);
    }

    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereColumn('stock', '<=', 'low_stock_threshold');
    }

    /** Search + filters, shared by the catalog, the admin list and the CSV export. */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['q'] ?? null, function ($q, $search) {
                $q->where(fn ($w) => $w->where('designation', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%"));
            })
            ->when($filters['family'] ?? null, fn ($q, $family) => $q->where('family', $family))
            ->when(is_numeric($filters['min_price'] ?? null), fn ($q) => $q->where('unit_price', '>=', $filters['min_price']))
            ->when(is_numeric($filters['max_price'] ?? null), fn ($q) => $q->where('unit_price', '<=', $filters['max_price']))
            ->when($filters['supplier_id'] ?? null, fn ($q, $id) => $q->where('supplier_id', $id))
            ->when(($filters['stock'] ?? null) === 'low', fn ($q) => $q->lowStock())
            ->when(($filters['in_stock'] ?? null) === '1', fn ($q) => $q->where('stock', '>', 0))
            // Admin list only: 'listed' = visible in the shop, 'hidden' = not visible
            ->when(($filters['visibility'] ?? null) === 'listed', fn ($q) => $q->where('is_listed', true))
            ->when(($filters['visibility'] ?? null) === 'hidden', fn ($q) => $q->where('is_listed', false));
    }

    /** Sort options of the catalog. */
    public const SORTS = [
        'name' => 'Nom (A → Z)',
        'price_asc' => 'Prix croissant',
        'price_desc' => 'Prix décroissant',
        'newest' => 'Nouveautés',
    ];

    public function scopeSortBy(Builder $query, ?string $sort): Builder
    {
        return match ($sort) {
            'price_asc' => $query->orderBy('unit_price')->orderBy('id'),
            'price_desc' => $query->orderByDesc('unit_price')->orderBy('id'),
            'newest' => $query->orderByDesc('id'),
            default => $query->orderBy('designation')->orderBy('id'),
        };
    }
}
