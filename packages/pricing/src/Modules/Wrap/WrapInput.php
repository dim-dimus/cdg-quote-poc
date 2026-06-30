<?php

declare(strict_types=1);

namespace CDG\Pricing\Modules\Wrap;

use CDG\Pricing\Contracts\CalculatorInput;

/**
 * Per-quote inputs for a vehicle wrap. All values are already resolved from the
 * database by the application layer; the engine does the AVERAGE() itself so
 * the averaging rule (DECISIONS.md D4) is covered by engine tests.
 *
 * Rates are in cents per sq ft (e.g. 850 = $8.50/sqft).
 * Labor hours are float (e.g. 20.0 hours).
 * Surface area is float sq ft (e.g. 200.5).
 * Complexity must be one of: 'easy', 'standard', 'complex', 'specialty'.
 */
final readonly class WrapInput implements CalculatorInput
{
    /**
     * @param string[] $selectedAddOnKeys  Add-on identifiers from the config catalog.
     */
    public function __construct(
        public float $laborLowHours,
        public float $laborHighHours,
        public float $sqFtLow,
        public float $sqFtHigh,
        public int $rateLowCents,
        public int $rateHighCents,
        public string $complexity,
        public array $selectedAddOnKeys,
    ) {
    }
}
