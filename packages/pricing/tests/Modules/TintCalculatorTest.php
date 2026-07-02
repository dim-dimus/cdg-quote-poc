<?php

declare(strict_types=1);

use CDG\Pricing\Contracts\CalculatorInput;
use CDG\Pricing\Modules\Tint\TintCalculator;
use CDG\Pricing\Modules\Tint\TintInput;
use CDG\Pricing\ValueObjects\PricingConfig;

/**
 * Unit tests for the Tint extensibility-demo module (see ModuleIsolationTest for
 * the "plugs in without touching existing code" proof).
 */

/** A minimal config; TintCalculator ignores it, proving modules need only the contract. */
function tintDemoConfig(): PricingConfig
{
    return new PricingConfig(
        shopRateCents:            11000,
        wasteMultiplier:          1.2,
        materialCostCentsPerSqFt: 220,
        complexityMultipliers:    ['easy' => 0.95, 'standard' => 1.0, 'complex' => 1.12, 'specialty' => 1.22],
        marginFloors:             ['reject' => 0.55, 'review' => 0.60, 'strong' => 0.65],
        addOns:                   [],
    );
}

it('registers service type "tint"', function () {
    expect((new TintCalculator())->serviceType())->toBe('tint');
});

it('prices windows × per-window price and cost', function () {
    $result = (new TintCalculator())->calculate(new TintInput(5, 6000, 2000), tintDemoConfig());

    expect($result->lines)->toHaveCount(1);

    $line = $result->lines[0];
    expect($line->serviceType)->toBe('tint')
        ->and($line->sellCents)->toBe(30000)   // 5 × 6000
        ->and($line->costCents)->toBe(10000)   // 5 × 2000
        ->and($line->description)->toContain('5 windows');
});

it('rejects a non-TintInput', function () {
    $notATint = new class implements CalculatorInput {};
    (new TintCalculator())->calculate($notATint, tintDemoConfig());
})->throws(InvalidArgumentException::class, 'TintCalculator expects a TintInput');
