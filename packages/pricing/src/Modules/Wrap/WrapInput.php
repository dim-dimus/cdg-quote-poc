<?php

declare(strict_types=1);

namespace CDG\Pricing\Modules\Wrap;

use CDG\Pricing\Contracts\CalculatorInput;

/**
 * Per-quote inputs for a vehicle wrap. All values are already resolved from the
 * database by the application layer; the engine does the AVERAGE() itself so
 * the averaging rule (DECISIONS.md D4) is covered by engine tests.
 *
 * Rates are in cents per sq ft (e.g. 1600 = $16.00/sqft).
 * Labor hours and surface area are floats.
 * Complexity must be one of: 'easy', 'standard', 'complex', 'specialty'.
 */
final readonly class WrapInput implements CalculatorInput
{
    /**
     * @param array<string, int|null> $addOnSelections
     *        Key = add-on identifier; value = override sell price in cents, or
     *        null to use the config catalog default. Add-on cost always comes
     *        from config (not overridable). See DECISIONS.md D7.
     * @param string|null $requestedFinish
     *        Informational only — no price effect today. Stored on the quote
     *        so finish-based pricing can be added later. See DECISIONS.md D9.
     */
    public function __construct(
        public float $laborLowHours,
        public float $laborHighHours,
        public float $sqFtLow,
        public float $sqFtHigh,
        public int $rateLowCents,
        public int $rateHighCents,
        public string $complexity,
        public array $addOnSelections,
        public ?string $requestedFinish = null,
    ) {
    }
}
