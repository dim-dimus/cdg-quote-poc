<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Global, admin-editable pricing knobs, stored as key/value rows so new settings
 * can be added without a schema change. PricingConfigRepository reads these and
 * builds a CDG\Pricing\ValueObjects\PricingConfig. Money settings are held in
 * cents (e.g. shop_rate_cents = 11000); multipliers/floors are decimals.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->decimal('value', 12, 4);
            $table->string('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_settings');
    }
};
