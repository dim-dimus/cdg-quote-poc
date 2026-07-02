<?php

declare(strict_types=1);

namespace CDG\Pricing\ValueObjects;

/**
 * Fully computed quote output. All money is integer cents (already rounded).
 * Gross margin is a ratio at full precision — round only for display outside
 * the engine. Immutable.
 */
final readonly class QuoteResult
{
    /**
     * @param ServiceLine[]                        $lines
     * @param int                                  $totalSellCents
     * @param int                                  $totalCostCents
     * @param int                                  $grossProfitCents
     * @param float                                $grossMargin  Full-precision ratio; 0.0 when sell is zero.
     * @param string                               $decision     e.g. "STRONG", "GOOD", "REVIEW", "REJECT / REPRICE"
     * @param array<string, BreakdownMetric[]>     $breakdown    Diagnostics keyed by service type, in display order.
     */
    public function __construct(
        public array $lines,
        public int $totalSellCents,
        public int $totalCostCents,
        public int $grossProfitCents,
        public float $grossMargin,
        public string $decision,
        public array $breakdown = [],
    ) {
    }
}
