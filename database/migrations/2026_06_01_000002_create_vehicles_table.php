<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The vehicle catalog (from the workbook's Vehicle Data sheet). Labor hours and
 * surface area are stored as low/high ranges; the engine averages them (D4).
 * No money lives here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('category');
            $table->decimal('labor_low_hours', 5, 2);
            $table->decimal('labor_high_hours', 5, 2);
            $table->unsignedInteger('sqft_low');
            $table->unsignedInteger('sqft_high');
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
