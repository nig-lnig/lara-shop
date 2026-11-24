<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Archiving instead of deleting.
 *
 * A record that is used by the order history (a client with orders, a
 * product in an order line, a supplier with products) must never be deleted,
 * otherwise old orders would point to nothing. We set "archived_at" instead:
 * the record disappears from normal lists but the history stays complete.
 */
trait Archivable
{
    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    public function archive(): void
    {
        $this->forceFill(['archived_at' => now()])->save();
    }

    public function unarchive(): void
    {
        $this->forceFill(['archived_at' => null])->save();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('archived_at');
    }

    /** $state: 'active' (default), 'archived' or 'all'. */
    public function scopeArchiveState(Builder $query, ?string $state): Builder
    {
        return match ($state) {
            'archived' => $query->whereNotNull('archived_at'),
            'all' => $query,
            default => $query->whereNull('archived_at'),
        };
    }
}
