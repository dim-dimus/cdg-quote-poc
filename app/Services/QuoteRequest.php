<?php

declare(strict_types=1);

namespace App\Services;

/**
 * The app-side, validated intent to build a quote. Carries only identifiers and
 * selections; QuoteService resolves these against the database and hands the
 * engine a WrapInput. Immutable.
 */
final readonly class QuoteRequest
{
    /**
     * @param array<string, int|null> $addOnSelections
     *        Add-on key => override sell price in cents, or null for catalog price (D7).
     */
    public function __construct(
        public int $vehicleId,
        public string $wrapTypeKey,
        public string $complexity,
        public array $addOnSelections = [],
        public ?string $requestedFinish = null,
        public ?string $customerName = null,
    ) {
    }
}
