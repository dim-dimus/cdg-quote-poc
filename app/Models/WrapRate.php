<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A wrap type rate card (per sq ft, in cents, low/high range). Looked up by key
 * when building a quote; the engine averages low/high (D4).
 */
class WrapRate extends Model
{
    protected $fillable = ['key', 'name', 'rate_low_cents', 'rate_high_cents'];

    protected $casts = [
        'rate_low_cents' => 'integer',
        'rate_high_cents' => 'integer',
    ];
}
