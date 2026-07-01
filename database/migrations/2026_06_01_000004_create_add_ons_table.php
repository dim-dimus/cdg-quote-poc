<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add-on catalog (from the workbook's AddOns sheet). Price and cost are in cents.
 * Per DECISIONS.md D7 the sell price can be overridden per quote, but cost always
 * comes from this catalog — so cost_cents is the single source of truth.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('add_ons', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->unsignedInteger('price_cents');
            $table->unsignedInteger('cost_cents');
            $table->string('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('add_ons');
    }
};
