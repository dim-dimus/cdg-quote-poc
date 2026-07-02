<?php

declare(strict_types=1);

namespace CDG\Pricing\ValueObjects;

/**
 * One labeled diagnostic value from a calculator's computation — the
 * intermediate figures a workbook shows alongside the totals (labor hours,
 * surface area, rate, material qty, revenue/cost components).
 *
 * This is display-only: metrics never feed back into pricing math. Money
 * metrics carry integer cents (unit "cents"); dimensional metrics carry a
 * float in their natural unit. Immutable.
 */
final readonly class BreakdownMetric
{
    /**
     * @param  string  $label  Human-readable row label, e.g. "Base Labor Hours".
     * @param  int|float  $value  Integer cents when unit is "cents"; otherwise a float.
     * @param  string  $unit  One of: "cents", "sqft", "hours".
     */
    public function __construct(
        public string $label,
        public int|float $value,
        public string $unit,
    ) {}
}
