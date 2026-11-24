<?php

/*
 * Small global helpers used in Blade views and CSV exports.
 * Loaded by composer.json ("autoload" > "files").
 */

if (! function_exists('money')) {
    /** "1234.5" -> "1 234,50 DH" (French display format). */
    function money(string|int|float|null $amount): string
    {
        return number_format((float) $amount, 2, ',', ' ').' '.config('shop.currency');
    }
}

if (! function_exists('fr_date')) {
    /** Carbon date -> "23/11/2026 14:05" (or "23/11/2026" without time). */
    function fr_date($date, bool $withTime = true): string
    {
        if (! $date) {
            return '';
        }

        return $date->format($withTime ? 'd/m/Y H:i' : 'd/m/Y');
    }
}
