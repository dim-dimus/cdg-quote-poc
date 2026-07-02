<?php

declare(strict_types=1);

use App\Livewire\Admin\PricingSettings;
use App\Livewire\Admin\Vehicles;
use App\Models\User;
use Database\Seeders\WorkbookSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * The admin screens are gated by the "admin" Gate: authenticated + is_admin.
 */
uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(WorkbookSeeder::class);
    $this->withoutVite();
});

dataset('admin routes', ['/admin/pricing', '/admin/vehicles']);

it('redirects guests to login', function (string $url) {
    $this->get($url)->assertRedirect('/login');
})->with('admin routes');

it('forbids a non-admin user', function (string $url) {
    $this->actingAs(User::factory()->create(['is_admin' => false]))
        ->get($url)
        ->assertForbidden();
})->with('admin routes');

it('lets an admin reach the pricing screen', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get('/admin/pricing')
        ->assertOk()
        ->assertSeeLivewire(PricingSettings::class);
});

it('lets an admin reach the vehicles screen', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get('/admin/vehicles')
        ->assertOk()
        ->assertSeeLivewire(Vehicles::class);
});
