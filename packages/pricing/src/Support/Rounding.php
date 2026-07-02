<?php

declare(strict_types=1);

namespace CDG\Pricing\Support;

/**
 * The single place where money is rounded in the engine.
 *
 * Intermediate math mirrors the Excel workbook (no premature rounding).
 * Call toCents() only on a final output value, never on an intermediate.
 * See DECISIONS.md D3.
 */
final class Rounding
{
    /**
     * Round a floating-point cent amount to the nearest whole cent, half-up.
     *
     * @param  float  $cents  An amount already expressed in cents (not dollars).
     */
    public static function toCents(float $cents): int
    {
        return (int) round($cents, 0, PHP_ROUND_HALF_UP);
    }
}
