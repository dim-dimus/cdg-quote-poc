<?php

declare(strict_types=1);

namespace CDG\Pricing;

use CDG\Pricing\Contracts\Calculator;

/**
 * Holds exactly one Calculator per service type.
 *
 * New service types are added by registering a new Calculator; existing entries
 * are never modified. This is the mechanism that lets future modules be added
 * without touching existing pricing code.
 */
final class CalculatorRegistry
{
    /** @var array<string, Calculator> */
    private array $calculators = [];

    public function register(Calculator $calculator): void
    {
        $type = $calculator->serviceType();

        if (isset($this->calculators[$type])) {
            throw new \LogicException(
                "A calculator is already registered for service type: {$type}"
            );
        }

        $this->calculators[$type] = $calculator;
    }

    public function get(string $serviceType): Calculator
    {
        return $this->calculators[$serviceType]
            ?? throw new \OutOfBoundsException(
                "No calculator registered for service type: {$serviceType}"
            );
    }

    public function has(string $serviceType): bool
    {
        return isset($this->calculators[$serviceType]);
    }

    /** @return string[] */
    public function types(): array
    {
        return array_keys($this->calculators);
    }
}
