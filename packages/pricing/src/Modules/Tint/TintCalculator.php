<?php

declare(strict_types=1);

namespace CDG\Pricing\Modules\Tint;

use CDG\Pricing\Contracts\Calculator;
use CDG\Pricing\Contracts\CalculatorInput;
use CDG\Pricing\Support\Rounding;
use CDG\Pricing\ValueObjects\CalculationResult;
use CDG\Pricing\ValueObjects\PricingConfig;
use CDG\Pricing\ValueObjects\ServiceLine;

/**
 * Prices a window-tint job: windows × per-window price / cost.
 *
 * EXTENSIBILITY DEMO (see TintInput). Deliberately trivial — the point is not
 * tint pricing accuracy but that this class plugs into the same Engine and
 * CalculatorRegistry as Wrap without any change to existing code. It shares the
 * Calculator contract and Support\Rounding, nothing else.
 */
final class TintCalculator implements Calculator
{
    public function serviceType(): string
    {
        return 'tint';
    }

    public function calculate(CalculatorInput $input, PricingConfig $config): CalculationResult
    {
        if (! $input instanceof TintInput) {
            throw new \InvalidArgumentException(
                'TintCalculator expects a TintInput, got '.$input::class.'.'
            );
        }

        $line = new ServiceLine(
            serviceType: 'tint',
            description: "Window tint ({$input->windowCount} windows)",
            sellCents: Rounding::toCents($input->windowCount * $input->pricePerWindowCents),
            costCents: Rounding::toCents($input->windowCount * $input->costPerWindowCents),
        );

        return new CalculationResult(lines: [$line]);
    }
}
