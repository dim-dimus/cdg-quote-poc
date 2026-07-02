<?php

declare(strict_types=1);

namespace CDG\Pricing\ValueObjects;

/**
 * One priced line on a quote. Money is integer cents (already rounded at the
 * engine boundary). Immutable.
 */
final readonly class ServiceLine
{
    public function __construct(
        public string $serviceType,
        public string $description,
        public int $sellCents,
        public int $costCents,
    ) {}

    public function grossProfitCents(): int
    {
        return $this->sellCents - $this->costCents;
    }
}
