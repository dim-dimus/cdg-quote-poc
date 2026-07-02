<?php

declare(strict_types=1);

namespace CDG\Pricing;

use CDG\Pricing\Contracts\CalculatorInput;
use CDG\Pricing\Support\Decision;
use CDG\Pricing\ValueObjects\PricingConfig;
use CDG\Pricing\ValueObjects\QuoteResult;

/**
 * Orchestrates calculators, aggregates their lines, and produces a QuoteResult.
 *
 * Usage: pass a map of serviceType => CalculatorInput for every service on the
 * quote. The engine dispatches each to its registered calculator, sums all
 * lines, then computes totals, margin, and decision.
 */
final class Engine
{
    public function __construct(private readonly CalculatorRegistry $registry) {}

    /**
     * Run the given calculators and return a fully priced QuoteResult.
     *
     * @param  array<string, CalculatorInput>  $inputs  serviceType → input
     */
    public function run(array $inputs, PricingConfig $config): QuoteResult
    {
        $lines = [];
        $breakdown = [];

        foreach ($inputs as $serviceType => $input) {
            $calculator = $this->registry->get($serviceType);
            $result = $calculator->calculate($input, $config);

            foreach ($result->lines as $line) {
                $lines[] = $line;
            }

            $breakdown[$serviceType] = $result->breakdown;
        }

        $totalSellCents = 0;
        $totalCostCents = 0;

        foreach ($lines as $line) {
            $totalSellCents += $line->sellCents;
            $totalCostCents += $line->costCents;
        }

        $grossProfitCents = $totalSellCents - $totalCostCents;
        $grossMargin = $totalSellCents > 0
            ? $grossProfitCents / $totalSellCents
            : 0.0;

        return new QuoteResult(
            lines: $lines,
            totalSellCents: $totalSellCents,
            totalCostCents: $totalCostCents,
            grossProfitCents: $grossProfitCents,
            grossMargin: $grossMargin,
            decision: Decision::fromMargin($grossMargin, $config),
            breakdown: $breakdown,
        );
    }
}
