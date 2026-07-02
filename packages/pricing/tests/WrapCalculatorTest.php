<?php

declare(strict_types=1);

use CDG\Pricing\Contracts\CalculatorInput;
use CDG\Pricing\Modules\Wrap\WrapCalculator;
use CDG\Pricing\Modules\Wrap\WrapInput;
use CDG\Pricing\ValueObjects\BreakdownMetric;
use CDG\Pricing\ValueObjects\CalculationResult;
use CDG\Pricing\ValueObjects\PricingConfig;
use CDG\Pricing\ValueObjects\ServiceLine;

/**
 * Unit tests for WrapCalculator in isolation (no Engine, no registry).
 * These test individual formula steps and edge cases; the full workbook
 * parity lives in ExcelParityTest.
 */
$calculator = new WrapCalculator;

/** Baseline config matching the fixture Shop Settings. */
function wrapTestConfig(): PricingConfig
{
    return new PricingConfig(
        shopRateCents: 11000,
        wasteMultiplier: 1.2,
        materialCostCentsPerSqFt: 220,
        complexityMultipliers: ['easy' => 0.95, 'standard' => 1.0, 'complex' => 1.12, 'specialty' => 1.22],
        marginFloors: ['reject' => 0.55, 'review' => 0.60, 'strong' => 0.65],
        addOns: [
            'ceramic_coating' => ['priceCents' => 70000, 'costCents' => 10000],
            'window_tint' => ['priceCents' => 40000, 'costCents' => 12000],
        ],
    );
}

/**
 * @param  array<string, int|null>  $addOnSelections
 */
function wrapTestInput(string $complexity = 'standard', array $addOnSelections = []): WrapInput
{
    return new WrapInput(
        laborLowHours: 22,
        laborHighHours: 28,
        sqFtLow: 280,
        sqFtHigh: 300,
        rateLowCents: 1600,
        rateHighCents: 2400,
        complexity: $complexity,
        addOnSelections: $addOnSelections,
    );
}

it('registers service type "wrap"', function () use ($calculator) {
    expect($calculator->serviceType())->toBe('wrap');
});

it('returns a wrap line and a labor line with no add-ons', function () use ($calculator) {
    $lines = $calculator->calculate(wrapTestInput(), wrapTestConfig())->lines;

    expect($lines)->toHaveCount(2)
        ->and($lines[0])->toBeInstanceOf(ServiceLine::class)
        ->and($lines[0]->description)->toBe('Vehicle wrap')
        ->and($lines[0]->sellCents)->toBe(580000)   // 290 sqft × 2000¢ × 1.0
        ->and($lines[0]->costCents)->toBe(76560)    // 290 × 1.2 × 220¢
        ->and($lines[1]->description)->toBe('Installation labor');
});

it('keeps labor at zero gross profit (D1: labor in both sell and cost)', function () use ($calculator) {
    $lines = $calculator->calculate(wrapTestInput(), wrapTestConfig())->lines;

    $labor = $lines[1];
    expect($labor->sellCents)->toBe(275000)              // 25h × 11000¢
        ->and($labor->costCents)->toBe(275000)
        ->and($labor->grossProfitCents())->toBe(0);
});

it('applies the complexity multiplier only to base wrap revenue (D5)', function () use ($calculator) {
    $lines = $calculator->calculate(wrapTestInput('complex'), wrapTestConfig())->lines;

    expect($lines[0]->sellCents)->toBe(649600)           // 290 × 2000 × 1.12
        ->and($lines[1]->sellCents)->toBe(275000);       // labor unaffected
});

it('uses the catalog add-on price when no override is given (D7)', function () use ($calculator) {
    $lines = $calculator->calculate(wrapTestInput('standard', ['window_tint' => null]), wrapTestConfig())->lines;

    expect($lines)->toHaveCount(3)
        ->and($lines[2]->description)->toBe('Add-on: window_tint')
        ->and($lines[2]->sellCents)->toBe(40000)
        ->and($lines[2]->costCents)->toBe(12000);
});

it('labels the add-on line with its display name when the catalog provides one', function () use ($calculator) {
    $config = new PricingConfig(
        shopRateCents: 11000,
        wasteMultiplier: 1.2,
        materialCostCentsPerSqFt: 220,
        complexityMultipliers: ['easy' => 0.95, 'standard' => 1.0, 'complex' => 1.12, 'specialty' => 1.22],
        marginFloors: ['reject' => 0.55, 'review' => 0.60, 'strong' => 0.65],
        addOns: ['window_tint' => ['name' => 'Window Tint', 'priceCents' => 40000, 'costCents' => 12000]],
    );

    $lines = $calculator->calculate(wrapTestInput('standard', ['window_tint' => null]), $config)->lines;

    expect($lines[2]->description)->toBe('Add-on: Window Tint');
});

it('falls back to the add-on key when the catalog has no display name', function () use ($calculator) {
    $lines = $calculator->calculate(wrapTestInput('standard', ['window_tint' => null]), wrapTestConfig())->lines;

    expect($lines[2]->description)->toBe('Add-on: window_tint');
});

it('overrides only the add-on sell price, never its cost (D7)', function () use ($calculator) {
    $lines = $calculator->calculate(wrapTestInput('standard', ['window_tint' => 55000]), wrapTestConfig())->lines;

    expect($lines[2]->sellCents)->toBe(55000)            // per-quote override
        ->and($lines[2]->costCents)->toBe(12000);        // cost stays from catalog
});

it('returns the workbook diagnostics in the breakdown, in row order', function () use ($calculator) {
    $result = $calculator->calculate(wrapTestInput('complex', ['window_tint' => null]), wrapTestConfig());

    expect($result)->toBeInstanceOf(CalculationResult::class);

    $byLabel = [];
    foreach ($result->breakdown as $metric) {
        expect($metric)->toBeInstanceOf(BreakdownMetric::class);
        $byLabel[$metric->label] = $metric;
    }

    // Order mirrors the workbook's PRICING + DECISION ENGINE panel.
    expect(array_keys($byLabel))->toBe([
        'Base Labor Hours', 'Base Surface Area', 'Rate per sq ft', 'Base Wrap Revenue',
        'Shop Rate', 'Labor Revenue', 'Material Cost', 'Material Order Qty',
        'Add-On Revenue', 'Add-On Cost',
    ]);

    // Values for this input: 290 sqft, 2000¢ rate, ×1.12, 25h labor, window_tint.
    expect($byLabel['Base Labor Hours']->value)->toBe(25.0)
        ->and($byLabel['Base Labor Hours']->unit)->toBe('hours')
        ->and($byLabel['Base Surface Area']->value)->toBe(290.0)
        ->and($byLabel['Base Surface Area']->unit)->toBe('sqft')
        ->and($byLabel['Rate per sq ft']->value)->toBe(2000)       // (1600+2400)/2, cents
        ->and($byLabel['Base Wrap Revenue']->value)->toBe(649600)  // 290 × 2000 × 1.12
        ->and($byLabel['Shop Rate']->value)->toBe(11000)
        ->and($byLabel['Labor Revenue']->value)->toBe(275000)
        ->and($byLabel['Material Cost']->value)->toBe(76560)       // 290 × 1.2 × 220
        ->and($byLabel['Material Order Qty']->value)->toBe(348.0)  // 290 × 1.2
        ->and($byLabel['Add-On Revenue']->value)->toBe(40000)
        ->and($byLabel['Add-On Cost']->value)->toBe(12000);
});

it('rejects an unknown complexity level', function () use ($calculator) {
    $calculator->calculate(wrapTestInput('extreme'), wrapTestConfig());
})->throws(InvalidArgumentException::class, 'Unknown complexity level: extreme.');

it('rejects an unknown add-on key', function () use ($calculator) {
    $calculator->calculate(wrapTestInput('standard', ['moon_roof' => null]), wrapTestConfig());
})->throws(InvalidArgumentException::class, 'Unknown add-on: moon_roof.');

it('rejects a non-WrapInput', function () use ($calculator) {
    $notAWrap = new class implements CalculatorInput {};
    $calculator->calculate($notAWrap, wrapTestConfig());
})->throws(InvalidArgumentException::class, 'WrapCalculator expects a WrapInput');
