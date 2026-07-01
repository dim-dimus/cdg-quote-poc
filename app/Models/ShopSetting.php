<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A single admin-editable pricing knob, stored as a key/value row.
 * Read by PricingConfigRepository to assemble the engine's PricingConfig.
 */
class ShopSetting extends Model
{
    protected $fillable = ['key', 'value', 'notes'];

    protected $casts = [
        'value' => 'float',
    ];
}
