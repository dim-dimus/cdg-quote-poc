<?php

declare(strict_types=1);

namespace CDG\Pricing\ValueObjects;

/**
 * What a Calculator returns: the priced lines plus an ordered, labeled
 * breakdown of the intermediate figures it computed along the way.
 *
 * The Engine sums the lines into quote totals and carries the breakdown
 * through to the QuoteResult (keyed by service type) so callers can show the
 * same diagnostics the workbook does. Immutable.
 */
final readonly class CalculationResult
{
    /**
     * @param ServiceLine[]     $lines     One or more priced lines (sell/cost in cents).
     * @param BreakdownMetric[] $breakdown Diagnostics in display order (may be empty).
     */
    public function __construct(
        public array $lines,
        public array $breakdown = [],
    ) {
    }
}
