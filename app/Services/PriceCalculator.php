<?php

namespace App\Services;

/**
 * The ONLY place where money is calculated.
 *
 * Rules (to explain in the presentation):
 *  - The database stores money as DECIMAL(10,2) (e.g. "12.50").
 *  - For calculations we convert to integer centimes (1250) so there is
 *    never a floating point error (0.1 + 0.2 problem).
 *  - Line total   = unit price x quantity            (exact, no rounding)
 *  - Subtotal     = sum of line totals                (exact)
 *  - Discount     = subtotal x rate / 100, rounded to the nearest centime,
 *                   halves rounded up (0.005 -> 0.01)
 *  - Total        = subtotal - discount
 *  - Outside this class, amounts are handled as decimal strings ("12.50").
 */
class PriceCalculator
{
    /** "12.5" or "12.50" -> 1250 (string parsing, no float). */
    public static function toCents(string|int $amount): int
    {
        $amount = trim((string) $amount);
        [$units, $decimals] = array_pad(explode('.', $amount, 2), 2, '0');
        $decimals = substr(str_pad($decimals, 2, '0'), 0, 2);

        return ((int) $units) * 100 + (int) $decimals;
    }

    /** 1250 -> "12.50" (format used by the database). */
    public static function fromCents(int $cents): string
    {
        return intdiv($cents, 100).'.'.str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }

    public static function lineTotal(int $unitCents, int $quantity): int
    {
        return $unitCents * $quantity;
    }

    /** Discount in centimes, rounded half up. */
    public static function discount(int $subtotalCents, int $rate): int
    {
        return intdiv($subtotalCents * $rate + 50, 100);
    }

    /**
     * Computes all totals for a list of lines.
     *
     * @param  array<int, array{unit_price: string, quantity: int}>  $lines
     * @return array{subtotal: string, discount_amount: string, total: string, lines: array}
     */
    public static function totals(array $lines, int $discountRate): array
    {
        $subtotal = 0;

        foreach ($lines as $i => $line) {
            $lineCents = self::lineTotal(self::toCents($line['unit_price']), (int) $line['quantity']);
            $lines[$i]['line_total'] = self::fromCents($lineCents);
            $subtotal += $lineCents;
        }

        $discount = self::discount($subtotal, $discountRate);

        return [
            'lines' => $lines,
            'subtotal' => self::fromCents($subtotal),
            'discount_amount' => self::fromCents($discount),
            'total' => self::fromCents($subtotal - $discount),
        ];
    }
}
