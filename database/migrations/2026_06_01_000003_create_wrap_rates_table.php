<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Wrap type rate cards (from the workbook's Rates sheet). Rates are per sq ft in
 * cents, as low/high ranges the engine averages (D4). Keyed for lookup by the
 * QuoteService (e.g. 'color_change', 'printed').
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wrap_rates', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->unsignedInteger('rate_low_cents');
            $table->unsignedInteger('rate_high_cents');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wrap_rates');
    }
};
