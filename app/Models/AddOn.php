<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A catalog add-on. Price is the default sell price (overridable per quote, D7);
 * cost is fixed and never overridable.
 */
class AddOn extends Model
{
    protected $fillable = ['key', 'name', 'price_cents', 'cost_cents', 'notes'];

    protected $casts = [
        'price_cents' => 'integer',
        'cost_cents' => 'integer',
    ];
}
