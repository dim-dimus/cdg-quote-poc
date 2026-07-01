<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A generated quote: the resolved inputs plus a frozen snapshot of the engine's
 * output. Totals are stored as integer cents in typed columns; the itemized
 * ServiceLine breakdown is stored as JSON so the quote can be re-displayed
 * exactly as sold, even after admin later changes prices (Phase 6).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name')->nullable();

            // Resolved inputs
            $table->foreignId('vehicle_id')->constrained()->restrictOnDelete();
            $table->foreignId('wrap_rate_id')->constrained()->restrictOnDelete();
            $table->string('complexity');
            $table->string('requested_finish')->nullable(); // D9: informational only
            $table->json('add_on_selections'); // { key: overrideSellCents|null } (D7)

            // Engine output snapshot (integer cents)
            $table->integer('total_sell_cents');
            $table->integer('total_cost_cents');
            $table->integer('gross_profit_cents');
            $table->decimal('gross_margin', 9, 8);
            $table->string('decision');
            $table->json('lines'); // itemized ServiceLine breakdown at quote time

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotes');
    }
};
