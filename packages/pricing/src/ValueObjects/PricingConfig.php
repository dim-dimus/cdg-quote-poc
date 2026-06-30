<?php

declare(strict_types=1);

namespace CDG\Pricing\ValueObjects;

/**
 * Global, admin-editable pricing knobs. Passed into the engine; never read from
 * the database inside the engine. Immutable.
 *
 * @property array{easy: float, standard: float, complex: float, specialty: float} $complexityMultipliers
 * @property array{reject: float, review: float, strong: float} $marginFloors
 * @property array<string, array{priceCents: int, costCents: int}> $addOns
 */
final readonly class PricingConfig
{
    /**
     * @param int   $shopRateCents              Shop labor rate in cents per hour (e.g. 11000 = $110/hr).
     * @param float $wasteMultiplier             Material order multiplier applied to surface area (e.g. 1.2).
     * @param int   $materialCostCentsPerSqFt    Material cost in cents per sq ft (e.g. 350 = $3.50).
     * @param array{easy: float, standard: float, complex: float, specialty: float} $complexityMultipliers
     * @param array{reject: float, review: float, strong: float} $marginFloors
     *        Margin thresholds: below reject → REJECT/REPRICE; below review → REVIEW;
     *        below strong → GOOD; otherwise → STRONG.
     * @param array<string, array{priceCents: int, costCents: int}> $addOns
     *        Add-on catalog keyed by add-on identifier.
     */
    public function __construct(
        public int $shopRateCents,
        public float $wasteMultiplier,
        public int $materialCostCentsPerSqFt,
        public array $complexityMultipliers,
        public array $marginFloors,
        public array $addOns,
    ) {
    }
}
