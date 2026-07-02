<?php

namespace App\Providers;

use App\Models\User;
use CDG\Pricing\CalculatorRegistry;
use CDG\Pricing\Engine;
use CDG\Pricing\Modules\Wrap\WrapCalculator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Wire the pricing engine once. Adding a future service module means
        // registering another calculator here — nothing else in the app changes.
        $this->app->singleton(Engine::class, function () {
            $registry = new CalculatorRegistry();
            $registry->register(new WrapCalculator());

            return new Engine($registry);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Only administrators may reach the admin (pricing / vehicle) screens.
        Gate::define('admin', fn (User $user) => $user->is_admin);
    }
}
