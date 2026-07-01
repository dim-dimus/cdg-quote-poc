<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\AddOn;
use App\Models\ShopSetting;
use CDG\Pricing\ValueObjects\PricingConfig;

/**
 * Translates the admin-editable database rows (shop_settings + add_ons) into the
 * engine's immutable PricingConfig value object.
 *
 * This is the boundary CLAUDE.md rule 5 describes: the engine never reads the
 * database; the app resolves config here and passes it in. All money is handed
 * to the engine as integer cents.
 */
final class PricingConfigRepository
{
    /**
     * Assemble a PricingConfig from current database settings.
     */
    public function load(): PricingConfig
    {
        $settings = ShopSetting::query()->pluck('value', 'key');

        return new PricingConfig(
            shopRateCents:            (int) round($settings['shop_rate_cents']),
            wasteMultiplier:          (float) $settings['waste_multiplier'],
            materialCostCentsPerSqFt: (int) round($settings['material_cost_cents_per_sqft']),
            complexityMultipliers: [
                'easy'      => (float) $settings['complexity_multiplier_easy'],
                'standard'  => (float) $settings['complexity_multiplier_standard'],
                'complex'   => (float) $settings['complexity_multiplier_complex'],
                'specialty' => (float) $settings['complexity_multiplier_specialty'],
            ],
            marginFloors: [
                'reject' => (float) $settings['margin_floor_reject'],
                'review' => (float) $settings['margin_floor_review'],
                'strong' => (float) $settings['margin_floor_strong'],
            ],
            addOns: $this->addOnCatalog(),
        );
    }

    /**
     * @return array<string, array{priceCents: int, costCents: int}>
     */
    private function addOnCatalog(): array
    {
        return AddOn::query()
            ->get()
            ->mapWithKeys(fn (AddOn $addOn) => [
                $addOn->key => [
                    'priceCents' => $addOn->price_cents,
                    'costCents'  => $addOn->cost_cents,
                ],
            ])
            ->all();
    }
}
