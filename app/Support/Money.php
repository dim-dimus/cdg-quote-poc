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
        return '$' . number_format($cents / 100, 2);
    }
}
