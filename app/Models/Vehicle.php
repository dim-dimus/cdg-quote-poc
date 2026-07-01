<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A catalog vehicle with its labor-hour and surface-area ranges. The engine
 * averages the low/high pairs itself (D4); this model just carries the data.
 */
class Vehicle extends Model
{
    protected $fillable = [
        'name', 'category',
        'labor_low_hours', 'labor_high_hours',
        'sqft_low', 'sqft_high', 'notes',
    ];

    protected $casts = [
        'labor_low_hours' => 'float',
        'labor_high_hours' => 'float',
        'sqft_low' => 'integer',
        'sqft_high' => 'integer',
    ];
}
