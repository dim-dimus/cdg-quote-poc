<?php

declare(strict_types=1);

use CDG\Pricing\CalculatorRegistry;
use CDG\Pricing\Engine;
use CDG\Pricing\Modules\Tint\TintCalculator;
use CDG\Pricing\Modules\Tint\TintInput;
use CDG\Pricing\Modules\Wrap\WrapCalculator;
use CDG\Pricing\Modules\Wrap\WrapInput;

/**
 * The architecture test the whole design is judged on: a NEW service module
 * (Tint) is added purely by dropping a folder under src/Modules/ and registering
 * it. No file under Engine, CalculatorRegistry, or Modules/Wrap was edited to
 * make this pass — the Calculator contract is the only shared surface.
 *
 * It proves two client requirements at once:
 *   1. "future modules can be added without touching existing code"
 *   2. "existing calculations can't be affected" — the wrap output is identical
 *      whether or not tint is registered alongside it.
 */
function isolationWrapInput(): WrapInput
{
    return new WrapInput(
        laborLowHours: 22,
        laborHighHours: 28,
        sqFtLow: 280,
        sqFtHigh: 300,
        rateLowCents: 1600,
        rateHighCents: 2400,
        complexity: 'standard',
        addOnSelections: [],
    );
}

it('prices a brand-new module through the same engine', function () {
    $registry = new CalculatorRegistry;
    $registry->register(new TintCalculator);   // a module Engine has never heard of
    $engine = new Engine($registry);

    $result = $engine->run(['tint' => new TintInput(6, 5000, 1500)], tintDemoConfig());

    expect($result->totalSellCents)->toBe(30000)   // 6 × 5000
        ->and($result->totalCostCents)->toBe(9000) // 6 × 1500
        ->and($result->lines)->toHaveCount(1)
        ->and($result->lines[0]->serviceType)->toBe('tint');
});

it('leaves existing wrap output identical when a new module is registered alongside it', function () {
    $config = tintDemoConfig();

    // Wrap alone.
    $wrapOnlyRegistry = new CalculatorRegistry;
    $wrapOnlyRegistry->register(new WrapCalculator);
    $wrapOnly = (new Engine($wrapOnlyRegistry))->run(['wrap' => isolationWrapInput()], $config);

    // Wrap + Tint. Tint registered FIRST to show ordering is irrelevant.
    $bothRegistry = new CalculatorRegistry;
    $bothRegistry->register(new TintCalculator);
    $bothRegistry->register(new WrapCalculator);
    $both = (new Engine($bothRegistry))->run([
        'wrap' => isolationWrapInput(),
        'tint' => new TintInput(6, 5000, 1500),
    ], $config);

    // Wrap lines are byte-for-byte unchanged by tint's presence.
    $wrapLinesFromBoth = array_values(array_filter($both->lines, fn ($l) => $l->serviceType === 'wrap'));
    expect($wrapLinesFromBoth)->toEqual($wrapOnly->lines);

    // Totals are simply wrap + tint; the wrap contribution is untouched.
    expect($both->totalSellCents)->toBe($wrapOnly->totalSellCents + 30000)
        ->and($both->totalCostCents)->toBe($wrapOnly->totalCostCents + 9000);
});
