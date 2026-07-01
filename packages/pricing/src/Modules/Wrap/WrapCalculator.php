<?php

declare(strict_types=1);

namespace CDG\Pricing\Modules\Wrap;

use CDG\Pricing\Contracts\Calculator;
use CDG\Pricing\Contracts\CalculatorInput;
use CDG\Pricing\Support\Rounding;
use CDG\Pricing\ValueObjects\PricingConfig;
use CDG\Pricing\ValueObjects\ServiceLine;

/**
 * Prices a vehicle wrap job. Implements the Front Desk formula map from the
 * Excel workbook. See DECISIONS.md for resolved ambiguities and
 * .claude/skills/cdg-pricing/SKILL.md for the verified formulas.
 *
 * All intermediate math is carried at full float precision in cents; money is
 * rounded to whole cents only at the ServiceLine boundary, via Support\Rounding
 * (DECISIONS.md D3). The engine sums these lines into the quote totals.
 *
 * Line breakdown (sell / cost in cents):
 *   1. Vehicle wrap       — sell = base wrap revenue, cost = material cost
 *   2. Installation labor — sell = cost = labor revenue (nets $0 GP, DECISIONS.md D1)
 *   3. Add-on (per key)   — sell = per-quote override or catalog price, cost = catalog cost (D7)
 */
final class WrapCalculator implements Calculator
{
    public function serviceType(): string
    {
        return 'wrap';
    }

    /**
     * @return ServiceLine[]
     */
    public function calculate(CalculatorInput $input, PricingConfig $config): array
    {
        if (! $input instanceof WrapInput) {
            throw new \InvalidArgumentException(
                'WrapCalculator expects a WrapInput, got ' . $input::class . '.'
            );
        }

        $complexityMultiplier = $config->complexityMultipliers[$input->complexity]
            ?? throw new \InvalidArgumentException(
                "Unknown complexity level: {$input->complexity}."
            );

        // Averages (DECISIONS.md D4) — always the midpoint of the low/high pair.
        $laborHours = ($input->laborLowHours + $input->laborHighHours) / 2;
        $sqFt       = ($input->sqFtLow + $input->sqFtHigh) / 2;
        $rateCents  = ($input->rateLowCents + $input->rateHighCents) / 2;

        // Revenue and cost components, full precision in cents.
        $baseWrapRevCents  = $sqFt * $rateCents * $complexityMultiplier; // D5: multiplier on wrap rev only
        $laborRevCents     = $laborHours * $config->shopRateCents;       // D1: appears in sell AND cost
        $materialQtySqFt   = $sqFt * $config->wasteMultiplier;           // D2: waste multiplier
        $materialCostCents = $materialQtySqFt * $config->materialCostCentsPerSqFt;

        $lines = [
            new ServiceLine(
                serviceType: 'wrap',
                description: 'Vehicle wrap',
                sellCents:   Rounding::toCents($baseWrapRevCents),
                costCents:   Rounding::toCents($materialCostCents),
            ),
            new ServiceLine(
                serviceType: 'wrap',
                description: 'Installation labor',
                sellCents:   Rounding::toCents($laborRevCents),
                costCents:   Rounding::toCents($laborRevCents),
            ),
        ];

        // Add-ons: sell price is overridable per quote (D7); cost never is.
        foreach ($input->addOnSelections as $key => $overrideSellCents) {
            $addOn = $config->addOns[$key]
                ?? throw new \InvalidArgumentException("Unknown add-on: {$key}.");

            $lines[] = new ServiceLine(
                serviceType: 'wrap',
                description: "Add-on: {$key}",
                sellCents:   $overrideSellCents ?? $addOn['priceCents'],
                costCents:   $addOn['costCents'],
            );
        }

        return $lines;
    }
}
