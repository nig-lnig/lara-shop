<?php

namespace App\Models\Concerns;

/**
 * Generates the business code shown in the UI (Cli-000001, Fr-000001, ...).
 *
 * How it works: right after the row is inserted, the database has given it a
 * unique numeric id. We build the code from that id, so two codes can never
 * collide, and the unique index in the migration double-checks it.
 *
 * A model using this trait declares:
 *   protected string $codePrefix = 'Cli';   // required
 *   protected string $codeColumn = 'code';  // optional, defaults to "code"
 */
trait HasBusinessCode
{
    protected static function bootHasBusinessCode(): void
    {
        static::created(function ($model) {
            $column = $model->codeColumn ?? 'code';

            if (empty($model->{$column})) {
                $model->{$column} = $model->codePrefix.'-'.str_pad((string) $model->id, 6, '0', STR_PAD_LEFT);
                $model->saveQuietly(); // save without firing events again
            }
        });
    }
}
