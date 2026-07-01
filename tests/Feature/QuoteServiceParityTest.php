<?php

declare(strict_types=1);

use App\Models\Vehicle;
use App\Models\WrapRate;
use App\Services\QuoteRequest;
use App\Services\QuoteService;
use Database\Seeders\WorkbookSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Phase 4 exit criterion: a quote created through the full DB-backed stack
 * (WorkbookSeeder → Vehicle/WrapRate/config in the database → QuoteService →
 * Engine → persisted Quote) reproduces every Excel parity fixture to the cent.
 *
 * This ties the persistence layer back to the same ground truth the engine is
 * tested against, and cross-checks that the seeded workbook data matches the
 * fixture inputs.
 */

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(WorkbookSeeder::class);
});

$fixture = json_decode(
    file_get_contents(dirname(__DIR__, 2) . '/packages/pricing/tests/Fixtures/wrap-cases.json'),
    associative: true,
);

dataset('workbook_cases', (function () use ($fixture) {
    $cases = [];
    foreach ($fixture['cases'] as $case) {
        $cases[$case['id']] = [$case];
    }

    return $cases;
})());

it('reproduces the workbook fixture through QuoteService', function (array $case) {
    $input = $case['input'];
    $expected = $case['expected'];

    $vehicle = Vehicle::where('name', $case['vehicle'])->firstOrFail();
    $wrapRate = WrapRate::where('rate_low_cents', $input['rateLowCents'])
        ->where('rate_high_cents', $input['rateHighCents'])
        ->firstOrFail();

    // The seeded vehicle stats must equal the fixture inputs (both from the workbook).
    expect((float) $vehicle->labor_low_hours)->toBe((float) $input['laborLowHours'], "{$case['id']}: labor low")
        ->and((float) $vehicle->labor_high_hours)->toBe((float) $input['laborHighHours'], "{$case['id']}: labor high")
        ->and($vehicle->sqft_low)->toBe($input['sqFtLow'], "{$case['id']}: sqft low")
        ->and($vehicle->sqft_high)->toBe($input['sqFtHigh'], "{$case['id']}: sqft high");

    $quote = app(QuoteService::class)->create(new QuoteRequest(
        vehicleId:       $vehicle->id,
        wrapTypeKey:     $wrapRate->key,
        complexity:      $input['complexity'],
        addOnSelections: $input['addOnSelections'],
        requestedFinish: $input['requestedFinish'],
    ));

    // Persisted totals match the workbook to the cent.
    expect($quote->total_sell_cents)->toBe($expected['totalSellCents'], "{$case['id']}: sell")
        ->and($quote->total_cost_cents)->toBe($expected['totalCostCents'], "{$case['id']}: cost")
        ->and($quote->gross_profit_cents)->toBe($expected['grossProfitCents'], "{$case['id']}: gross profit")
        ->and($quote->gross_margin)->toEqualWithDelta($expected['grossMargin'], 1e-8, "{$case['id']}: margin")
        ->and($quote->decision)->toBe($expected['decision'], "{$case['id']}: decision");

    // The persisted itemized breakdown sums back to the stored totals.
    $lineSell = array_sum(array_column($quote->lines, 'sellCents'));
    $lineCost = array_sum(array_column($quote->lines, 'costCents'));
    expect($lineSell)->toBe($quote->total_sell_cents, "{$case['id']}: line sell sum")
        ->and($lineCost)->toBe($quote->total_cost_cents, "{$case['id']}: line cost sum");
})->with('workbook_cases');
