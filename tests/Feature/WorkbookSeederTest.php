<?php

declare(strict_types=1);

use App\Models\AddOn;
use App\Models\ShopSetting;
use App\Models\Vehicle;
use App\Models\WrapRate;
use Database\Seeders\WorkbookSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Sanity checks on the workbook data load: counts and a couple of spot values.
 * Guards against a corrupted vehicles.json or a mis-transcribed config value.
 */
uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(WorkbookSeeder::class);
});

it('loads the full workbook reference data', function () {
    expect(Vehicle::count())->toBe(177)   // Vehicle Data sheet, placeholder row excluded
        ->and(WrapRate::count())->toBe(2)
        ->and(AddOn::count())->toBe(7)
        ->and(ShopSetting::count())->toBe(10);
});

it('is idempotent — re-seeding does not duplicate rows', function () {
    $this->seed(WorkbookSeeder::class);

    expect(Vehicle::count())->toBe(177)
        ->and(WrapRate::count())->toBe(2)
        ->and(AddOn::count())->toBe(7);
});

it('seeds config values as integer cents / decimals matching the workbook', function () {
    expect((int) ShopSetting::where('key', 'shop_rate_cents')->value('value'))->toBe(11000)
        ->and((float) ShopSetting::where('key', 'waste_multiplier')->value('value'))->toBe(1.2)
        ->and(AddOn::where('key', 'ceramic_coating')->value('price_cents'))->toBe(70000)
        ->and(AddOn::where('key', 'ceramic_coating')->value('cost_cents'))->toBe(10000)
        ->and(WrapRate::where('key', 'color_change')->value('rate_high_cents'))->toBe(2400);
});
