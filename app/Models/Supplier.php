<?php

namespace App\Models;

use App\Models\Concerns\Archivable;
use App\Models\Concerns\HasBusinessCode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use Archivable, HasBusinessCode, HasFactory;

    protected string $codePrefix = 'Fr';

    protected $fillable = ['name', 'city'];

    protected function casts(): array
    {
        return ['archived_at' => 'datetime'];
    }

    /** Rule 7: a supplier can supply one or more products. */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['q'] ?? null, function ($q, $search) {
                $q->where(fn ($w) => $w->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%"));
            });
    }
}
