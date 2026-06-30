<?php

declare(strict_types=1);

namespace CDG\Pricing\Modules\Wrap;

use CDG\Pricing\Contracts\Calculator;
use CDG\Pricing\Contracts\CalculatorInput;
use CDG\Pricing\ValueObjects\PricingConfig;
use CDG\Pricing\ValueObjects\ServiceLine;

/**
 * Prices a vehicle wrap job. Implements the Front Desk formula map from the
 * Excel workbook. See DECISIONS.md for resolved ambiguities.
 *
 * Calculation logic is implemented in Phase 3. This skeleton satisfies Phase 1
 * (contracts wired, package compiles, framework-free).
 */
final class WrapCalculator implements Calculator
{
    public function serviceType(): string
    {
        return 'wrap';
    }

    /**
     * @param WrapInput $input
     * @return ServiceLine[]
     */
    public function calculate(CalculatorInput $input, PricingConfig $config): array
    {
        throw new \LogicException('WrapCalculator::calculate() is not yet implemented — Phase 3.');
    }
}
