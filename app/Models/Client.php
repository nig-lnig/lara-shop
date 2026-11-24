<?php

namespace App\Models;

use App\Models\Concerns\Archivable;
use App\Models\Concerns\HasBusinessCode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Client extends Model
{
    use Archivable, HasBusinessCode, HasFactory;

    protected string $codePrefix = 'Cli';

    /** Type Client (PDF page 6). Code => French label. */
    public const TYPES = [
        'PU' => 'Secteur public',
        'PA' => 'Particulier',
        'LI' => 'Profession libérale',
        'AS' => 'Association',
        'DI' => 'Divers',
    ];

    public const DEFAULT_TYPE = 'PA';

    protected $fillable = [
        'name', 'street', 'postal_code', 'city', 'type', 'special_discount',
    ];

    protected function casts(): array
    {
        return [
            'special_discount' => 'integer',
            'archived_at' => 'datetime',
        ];
    }

    // ---- Relations -------------------------------------------------------

    /** Rule 1: a client can place one or more orders. */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /** The login account linked to this client (may be null). */
    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    // ---- Helpers ---------------------------------------------------------

    public function typeLabel(): string
    {
        return self::TYPES[$this->type ?? ''] ?? (string) $this->type;
    }

    /** List filters, shared by the admin page and the CSV export. */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['q'] ?? null, function ($q, $search) {
                $q->where(fn ($w) => $w->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%"));
            })
            ->when($filters['type'] ?? null, fn ($q, $type) => $q->where('type', $type));
    }
}
