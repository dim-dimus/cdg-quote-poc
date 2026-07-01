<?php

declare(strict_types=1);

use CDG\Pricing\Modules\Wrap\WrapCalculator;
use CDG\Pricing\Modules\Wrap\WrapInput;
use CDG\Pricing\ValueObjects\PricingConfig;

/**
 * Unit tests for WrapCalculator in isolation (no Engine, no registry).
 * These test individual formula steps and edge cases.
 * Implemented in Phase 3 alongside WrapCalculator::calculate().
 */

$calculator = new WrapCalculator();

it('registers service type "wrap"', function () use ($calculator) {
    expect($calculator->serviceType())->toBe('wrap');
});
