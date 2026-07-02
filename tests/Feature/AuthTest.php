<?php

declare(strict_types=1);

use App\Livewire\Auth\Login;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

/**
 * The Front Desk is gated behind login. These cover the guard and the login flow.
 */

uses(RefreshDatabase::class);

it('redirects guests from the front desk to login', function () {
    $this->get('/')->assertRedirect('/login');
});

it('lets an authenticated user reach the front desk', function () {
    $this->withoutVite();

    $this->actingAs(User::factory()->create())
        ->get('/')
        ->assertOk()
        ->assertSeeLivewire(App\Livewire\FrontDesk\QuoteBuilder::class);
});

it('signs a user in with valid credentials', function () {
    User::factory()->create([
        'email' => 'staff@calidreamgarage.test',
        'password' => Hash::make('password'),
    ]);

    Livewire::test(Login::class)
        ->set('email', 'staff@calidreamgarage.test')
        ->set('password', 'password')
        ->call('login')
        ->assertHasNoErrors()
        ->assertRedirect(route('front-desk'));

    expect(auth()->check())->toBeTrue();
});

it('rejects invalid credentials', function () {
    User::factory()->create([
        'email' => 'staff@calidreamgarage.test',
        'password' => Hash::make('password'),
    ]);

    Livewire::test(Login::class)
        ->set('email', 'staff@calidreamgarage.test')
        ->set('password', 'wrong')
        ->call('login')
        ->assertHasErrors('email');

    expect(auth()->check())->toBeFalse();
});
