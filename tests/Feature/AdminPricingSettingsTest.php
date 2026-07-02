<?php

declare(strict_types=1);

use App\Livewire\Admin\PricingSettings;
use App\Models\AddOn;
use App\Models\ShopSetting;
use App\Models\User;
use App\Services\QuoteRequest;
use App\Services\QuoteService;
use App\Models\Vehicle;
use Database\Seeders\WorkbookSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

/**
 * Phase 6 exit: editing pricing in admin changes the next quote's result with
 * no deploy. These drive the Livewire admin screen and re-price through the
 * engine to prove the config round-trip.
 */

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(WorkbookSeeder::class);
    $this->actingAs(User::factory()->admin()->create());
    $this->withoutVite();
});

function priceTransit(): int
{
    $vehicleId = Vehicle::where('name', 'Ford Transit')->value('id');

    return app(QuoteService::class)->price(new QuoteRequest(
        vehicleId:   $vehicleId,
        wrapTypeKey: 'color_change',
        complexity:  'standard',
    ))->totalSellCents;
}

it('changes the next quote when the shop rate is edited (exit criterion)', function () {
    $before = priceTransit();

    Livewire::test(PricingSettings::class)
        ->set('shopRate', '150.00')   // was $110/hr
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('scroll-to-top');   // banner brought into view

    expect(ShopSetting::where('key', 'shop_rate_cents')->value('value'))->toEqual(15000)
        ->and(priceTransit())->not->toBe($before);   // next quote reflects it
});

it('stores edited money as integer cents', function () {
    $tint = AddOn::where('key', 'window_tint')->firstOrFail();

    Livewire::test(PricingSettings::class)
        ->set("addOns.{$tint->id}.price", '525.50')
        ->set("addOns.{$tint->id}.cost", '130')
        ->call('save')
        ->assertHasNoErrors();

    $tint->refresh();
    expect($tint->price_cents)->toBe(52550)
        ->and($tint->cost_cents)->toBe(13000);
});

it('rejects margin floors that are not ascending', function () {
    Livewire::test(PricingSettings::class)
        ->set('marginReject', '0.70')
        ->set('marginReview', '0.60')   // below reject → invalid
        ->set('marginStrong', '0.80')
        ->call('save')
        ->assertHasErrors('marginReview')
        ->assertNotDispatched('scroll-to-top');   // no scroll on a failed save

    // Unchanged in the DB.
    expect(ShopSetting::where('key', 'margin_floor_reject')->value('value'))->toEqual(0.55);
});

it('validates that money fields are numeric and non-negative', function () {
    Livewire::test(PricingSettings::class)
        ->set('shopRate', '-5')
        ->call('save')
        ->assertHasErrors(['shopRate' => 'min']);
});
