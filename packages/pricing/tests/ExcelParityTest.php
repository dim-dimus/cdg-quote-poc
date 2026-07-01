<?php

declare(strict_types=1);

use CDG\Pricing\CalculatorRegistry;
use CDG\Pricing\Engine;
use CDG\Pricing\Modules\Wrap\WrapCalculator;
use CDG\Pricing\Modules\Wrap\WrapInput;
use CDG\Pricing\ValueObjects\PricingConfig;

/**
 * Headline parity test: the engine must reproduce every workbook output to the cent.
 *
 * Fixtures are extracted from CDG_workbook.xlsx and are the ground truth.
 * NEVER edit a fixture value to make this test pass — fix the engine instead.
 */

function buildConfig(array $cfg): PricingConfig
{
    return new PricingConfig(
        shopRateCents:             $cfg['shopRateCents'],
        wasteMultiplier:           $cfg['wasteMultiplier'],
        materialCostCentsPerSqFt:  $cfg['materialCostCentsPerSqFt'],
        complexityMultipliers:     $cfg['complexityMultipliers'],
        marginFloors:              $cfg['marginFloors'],
        addOns:                    $cfg['addOns'],
    );
}

function buildInput(array $input): WrapInput
{
    return new WrapInput(
        laborLowHours:    $input['laborLowHours'],
        laborHighHours:   $input['laborHighHours'],
        sqFtLow:          $input['sqFtLow'],
        sqFtHigh:         $input['sqFtHigh'],
        rateLowCents:     $input['rateLowCents'],
        rateHighCents:    $input['rateHighCents'],
        complexity:        $input['complexity'],
        addOnSelections:   $input['addOnSelections'],
        requestedFinish:   $input['requestedFinish'],
    );
}

function makeEngine(): Engine
{
    $registry = new CalculatorRegistry();
    $registry->register(new WrapCalculator());
    return new Engine($registry);
}

$fixture = json_decode(
    file_get_contents(__DIR__ . '/Fixtures/wrap-cases.json'),
    associative: true,
);

$config = buildConfig($fixture['config']);
$engine = makeEngine();

dataset('wrap_cases', array_map(
    fn(array $case) => [$case['id'], $case['description'], $case['input'], $case['expected']],
    $fixture['cases'],
));

it('reproduces workbook output to the cent: $description', function (
    string $id,
    string $description,
    array  $input,
    array  $expected,
) use ($config, $engine) {
    $result = $engine->run(['wrap' => buildInput($input)], $config);

    expect($result->totalSellCents)  ->toBe($expected['totalSellCents'],   "{$id}: totalSellCents");
    expect($result->totalCostCents)  ->toBe($expected['totalCostCents'],   "{$id}: totalCostCents");
    expect($result->grossProfitCents)->toBe($expected['grossProfitCents'], "{$id}: grossProfitCents");
    expect($result->grossMargin)     ->toBeCloseTo($expected['grossMargin'], 8, "{$id}: grossMargin");
    expect($result->decision)        ->toBe($expected['decision'],         "{$id}: decision");
})->with('wrap_cases');
