<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A persisted quote: resolved inputs + a frozen snapshot of the engine output.
 * Totals are integer cents; `lines` is the itemized ServiceLine breakdown as it
 * was sold. This model is app-only — the engine never sees it.
 */
class Quote extends Model
{
    protected $fillable = [
        'user_id',
        'customer_name',
        'vehicle_id', 'wrap_rate_id', 'complexity', 'requested_finish',
        'add_on_selections',
        'total_sell_cents', 'total_cost_cents', 'gross_profit_cents',
        'gross_margin', 'decision', 'lines', 'breakdown',
        'input_snapshot', 'config_snapshot',
    ];

    protected $casts = [
        'add_on_selections' => 'array',
        'lines' => 'array',
        'breakdown' => 'array',
        'input_snapshot' => 'array',
        'config_snapshot' => 'array',
        'total_sell_cents' => 'integer',
        'total_cost_cents' => 'integer',
        'gross_profit_cents' => 'integer',
        'gross_margin' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function wrapRate(): BelongsTo
    {
        return $this->belongsTo(WrapRate::class);
    }
}
