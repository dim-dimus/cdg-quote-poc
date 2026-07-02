<?php

declare(strict_types=1);

namespace CDG\Pricing\Contracts;

/**
 * Marker interface for a module's immutable, validated input value object.
 *
 * Each calculator defines its own input shape (e.g. WrapInput) implementing
 * this interface. Inputs should be `readonly` and carry only the per-quote
 * specifics already resolved by the application layer — never raw DB rows.
 */
interface CalculatorInput {}
