<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Presentation-layer money formatting: integer cents → display dollars.
 *
 * This is display only. It must NOT be used for pricing math — all monetary
 * calculation and rounding lives in the engine (CDG\Pricing\Support\Rounding).
 */
final class Money
{
    /** e.g. 855000 → "$8,550.00" */
    public static function format(int $cents): string
    {
        return '$'.number_format($cents / 100, 2);
    }

    /**
     * Cents → a plain decimal string for an <input> value, e.g. 11000 → "110.00".
     * No currency symbol or thousands separators (would break numeric parsing).
     */
    public static function forInput(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }

    /**
     * Dollars (as entered in a form) → integer cents, at the app boundary.
     * This is a boundary conversion, not pricing math: the engine still owns all
     * calculation and rounding. e.g. "110.00" → 11000.
     */
    public static function toCents(int|float|string $dollars): int
    {
        return (int) round(((float) $dollars) * 100);
    }
}
