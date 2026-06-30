<?php

declare(strict_types=1);

namespace CDG\Pricing\Contracts;

use CDG\Pricing\ValueObjects\PricingConfig;
use CDG\Pricing\ValueObjects\ServiceLine;

/**
 * A Calculator prices one service type (wrap, ppf, tint, ceramic, detailing...).
 *
 * It is a pure function: validated input + pricing config in, priced lines out.
 * Implementations MUST NOT touch the database, HTTP, the clock, randomness, or
 * any framework. Everything they need is provided via the arguments.
 */
interface Calculator
{
    /**
     * Stable, unique machine key for this service type, e.g. "wrap", "tint".
     * Used by the CalculatorRegistry; must be unique across all modules.
     */
    public function serviceType(): string;

    /**
     * Produce the priced line(s) for this service from validated input.
     *
     * @return ServiceLine[] one or more lines (description, sell, cost in cents)
     */
    public function calculate(CalculatorInput $input, PricingConfig $config): array;
}
