<?php

declare(strict_types=1);

use App\Livewire\FrontDesk\QuoteBuilder;
use App\Models\Quote;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\WorkbookSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

/**
 * Phase 5 exit: the Front Desk UI shows numbers equal to the workbook, live.
 * These drive the full chain UI → QuoteService → Engine → workbook fixtures.
 */

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(WorkbookSeeder::class);
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

function transit(): Vehicle
{
    return Vehicle::where('name', 'Ford Transit')->firstOrFail();
}

it('shows workbook pricing live for a selected vehicle', function () {
    Livewire::test(QuoteBuilder::class)
        ->call('selectVehicle', transit()->id)
        ->set('wrapTypeKey', 'color_change')
        ->set('complexity', 'standard')
        ->assertSee('$8,550.00')   // total sell   (855000)
        ->assertSee('$3,515.60')   // total cost   (351560)
        ->assertSee('$5,034.40')   // gross profit (503440)
        ->assertSee('58.9%')       // gross margin (0.5888)
        ->assertSee('REVIEW');     // decision
});

it('shows the workbook pricing-engine breakdown in the panel', function () {
    Livewire::test(QuoteBuilder::class)
        ->call('selectVehicle', transit()->id)
        ->set('wrapTypeKey', 'color_change')
        ->set('complexity', 'standard')
        ->assertSee('Pricing engine')
        ->assertSee('Base Labor Hours')
        ->assertSee('Base Surface Area')
        ->assertSee('Rate per sq ft')
        ->assertSee('Material Order Qty')
        ->assertSee('Add-On Revenue')
        ->assertSee('$110.00');   // Shop Rate (11000¢)
});

it('recomputes live when an add-on is toggled', function () {
    Livewire::test(QuoteBuilder::class)
        ->call('selectVehicle', transit()->id)
        ->set('wrapTypeKey', 'color_change')
        ->set('complexity', 'standard')
        ->set('addonSelected.window_tint', true)
        ->assertSee('$8,950.00')      // 855000 + 40000 tint
        ->assertSee('Add-on: Window Tint')   // display name, not the slug
        ->assertDontSee('Add-on: window_tint');
});

it('applies a per-quote add-on price override (D7)', function () {
    Livewire::test(QuoteBuilder::class)
        ->call('selectVehicle', transit()->id)
        ->set('wrapTypeKey', 'color_change')
        ->set('complexity', 'standard')
        ->set('addonSelected.window_tint', true)
        ->set('addonOverride.window_tint', '550')  // $550 instead of $400
        ->assertSee('$9,100.00');  // 855000 + 55000
});

it('changes the decision when inputs push margin across a floor', function () {
    Livewire::test(QuoteBuilder::class)
        ->call('selectVehicle', transit()->id)
        ->set('wrapTypeKey', 'color_change')
        ->set('complexity', 'specialty')  // higher multiplier → GOOD
        ->assertSee('GOOD');
});

it('filters vehicles in the combobox and selects one', function () {
    Livewire::test(QuoteBuilder::class)
        ->set('vehicleSearch', 'cyber')
        ->assertSee('Tesla Cybertruck')
        ->assertDontSee('Ford Transit')
        ->call('selectVehicle', Vehicle::where('name', 'Tesla Cybertruck')->value('id'))
        ->assertSet('vehicleName', 'Tesla Cybertruck');
});

it('persists a Quote snapshot on save', function () {
    Livewire::test(QuoteBuilder::class)
        ->call('selectVehicle', transit()->id)
        ->set('wrapTypeKey', 'color_change')
        ->set('complexity', 'standard')
        ->set('customerName', 'Acme Fleet')
        ->call('save')
        ->assertHasNoErrors();

    $quote = Quote::firstOrFail();
    expect($quote->total_sell_cents)->toBe(855000)
        ->and($quote->decision)->toBe('REVIEW')
        ->and($quote->customer_name)->toBe('Acme Fleet')
        ->and($quote->lines)->toHaveCount(2)            // wrap + labor
        ->and($quote->user_id)->toBe($this->user->id);  // creator recorded

    // Immutable snapshot: diagnostics + resolved inputs + config used.
    expect($quote->breakdown['wrap'])->toHaveCount(10)
        ->and($quote->input_snapshot['vehicleName'])->toBe('Ford Transit')
        ->and($quote->input_snapshot['rateLowCents'])->toBe(1600)  // color_change low
        ->and($quote->config_snapshot['shopRateCents'])->toBe(11000)
        ->and($quote->config_snapshot['addOns'])->toHaveKey('window_tint');
});

it('requires a vehicle before saving', function () {
    Livewire::test(QuoteBuilder::class)
        ->call('save')
        ->assertHasErrors(['vehicleId' => 'required']);

    expect(Quote::count())->toBe(0);
});
