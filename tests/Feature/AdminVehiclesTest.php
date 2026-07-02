<?php

declare(strict_types=1);

use App\Livewire\Admin\Vehicles;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\QuoteRequest;
use App\Services\QuoteService;
use Database\Seeders\WorkbookSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

/**
 * Admin vehicle catalog CRUD, including the guard that a vehicle referenced by
 * an existing quote cannot be deleted.
 */
uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(WorkbookSeeder::class);
    $this->actingAs(User::factory()->admin()->create());
    $this->withoutVite();
});

it('creates a vehicle', function () {
    Livewire::test(Vehicles::class)
        ->call('create')
        ->set('name', 'Test Van')
        ->set('category', 'Van')
        ->set('laborLow', '18')
        ->set('laborHigh', '22')
        ->set('sqftLow', '250')
        ->set('sqftHigh', '270')
        ->call('save')
        ->assertHasNoErrors();

    $vehicle = Vehicle::where('name', 'Test Van')->firstOrFail();
    expect($vehicle->labor_low_hours)->toBe(18.0)
        ->and($vehicle->labor_high_hours)->toBe(22.0)
        ->and($vehicle->sqft_low)->toBe(250)
        ->and($vehicle->sqft_high)->toBe(270);
});

it('edits an existing vehicle', function () {
    $vehicle = Vehicle::where('name', 'Ford Transit')->firstOrFail();

    Livewire::test(Vehicles::class)
        ->call('edit', $vehicle->id)
        ->set('sqftHigh', '999')
        ->call('save')
        ->assertHasNoErrors();

    expect($vehicle->fresh()->sqft_high)->toBe(999);
});

it('rejects a high value below its low', function () {
    Livewire::test(Vehicles::class)
        ->call('create')
        ->set('name', 'Bad Ranges')
        ->set('category', 'Van')
        ->set('laborLow', '20')
        ->set('laborHigh', '10')   // high < low
        ->set('sqftLow', '250')
        ->set('sqftHigh', '270')
        ->call('save')
        ->assertHasErrors('laborHigh');

    expect(Vehicle::where('name', 'Bad Ranges')->exists())->toBeFalse();
});

it('deletes an unreferenced vehicle', function () {
    $vehicle = Vehicle::create([
        'name' => 'Disposable', 'category' => 'Van',
        'labor_low_hours' => 10, 'labor_high_hours' => 12,
        'sqft_low' => 100, 'sqft_high' => 120,
    ]);

    Livewire::test(Vehicles::class)->call('delete', $vehicle->id);

    expect(Vehicle::whereKey($vehicle->id)->exists())->toBeFalse();
});

it('blocks deleting a vehicle referenced by a quote', function () {
    $vehicle = Vehicle::where('name', 'Ford Transit')->firstOrFail();

    app(QuoteService::class)->create(new QuoteRequest(
        vehicleId: $vehicle->id,
        wrapTypeKey: 'color_change',
        complexity: 'standard',
    ));

    Livewire::test(Vehicles::class)->call('delete', $vehicle->id);

    expect(Vehicle::whereKey($vehicle->id)->exists())->toBeTrue();
});
