<?php

declare(strict_types=1);

use App\Livewire\Admin\PricingSettings;
use App\Livewire\Admin\Vehicles;
use App\Livewire\Auth\Login;
use App\Livewire\FrontDesk\QuoteBuilder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/login', Login::class)->middleware('guest')->name('login');

Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect()->route('login');
})->middleware('auth')->name('logout');

Route::get('/', QuoteBuilder::class)->middleware('auth')->name('front-desk');

// Admin: pricing config + vehicle catalog, editable without a deploy (Phase 6).
Route::middleware(['auth', 'can:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/pricing', PricingSettings::class)->name('pricing');
    Route::get('/vehicles', Vehicles::class)->name('vehicles');
});
