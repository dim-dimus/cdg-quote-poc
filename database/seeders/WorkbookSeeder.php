<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AddOn;
use App\Models\ShopSetting;
use App\Models\Vehicle;
use App\Models\WrapRate;
use Illuminate\Database\Seeder;

/**
 * Loads all pricing reference data from the CDG workbook into the database.
 *
 * The small config tables (shop settings, wrap rates, add-ons) are inlined here
 * for auditability — each value maps directly to a workbook cell, dollars
 * converted to integer cents. The 177-row vehicle catalog is loaded from
 * database/seeders/data/vehicles.json (extracted from the Vehicle Data sheet).
 *
 * Idempotent: keyed upserts so re-seeding never duplicates rows.
 */
class WorkbookSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedShopSettings();
        $this->seedWrapRates();
        $this->seedAddOns();
        $this->seedVehicles();
    }

    /** Shop Settings sheet. Money in cents; multipliers/floors as decimals. */
    private function seedShopSettings(): void
    {
        $settings = [
            ['key' => 'shop_rate_cents',               'value' => 11000, 'notes' => '$110/hr'],
            ['key' => 'waste_multiplier',              'value' => 1.2,   'notes' => 'Order 1.2x surface area (D2)'],
            ['key' => 'material_cost_cents_per_sqft',  'value' => 220,   'notes' => '$2.20/sqft'],
            ['key' => 'complexity_multiplier_easy',      'value' => 0.95, 'notes' => 'Straight panels / fleet repeat'],
            ['key' => 'complexity_multiplier_standard',  'value' => 1.0,  'notes' => 'Default'],
            ['key' => 'complexity_multiplier_complex',   'value' => 1.12, 'notes' => 'Tighter curves / premium difficulty'],
            ['key' => 'complexity_multiplier_specialty', 'value' => 1.22, 'notes' => 'Wrangler / Cybertruck / exotics'],
            ['key' => 'margin_floor_reject', 'value' => 0.55, 'notes' => 'Below this → REJECT / REPRICE'],
            ['key' => 'margin_floor_review', 'value' => 0.60, 'notes' => 'Below this → REVIEW'],
            ['key' => 'margin_floor_strong', 'value' => 0.65, 'notes' => 'Below this → GOOD, otherwise STRONG'],
        ];

        foreach ($settings as $row) {
            ShopSetting::updateOrCreate(['key' => $row['key']], $row);
        }
    }

    /** Rates sheet (dollars → cents). */
    private function seedWrapRates(): void
    {
        $rates = [
            ['key' => 'color_change', 'name' => 'Color Change', 'rate_low_cents' => 1600, 'rate_high_cents' => 2400],
            ['key' => 'printed',      'name' => 'Printed',      'rate_low_cents' => 1800, 'rate_high_cents' => 2800],
        ];

        foreach ($rates as $row) {
            WrapRate::updateOrCreate(['key' => $row['key']], $row);
        }
    }

    /** AddOns sheet (dollars → cents). */
    private function seedAddOns(): void
    {
        $addOns = [
            ['key' => 'ceramic_coating', 'name' => 'Ceramic Coating', 'price_cents' => 70000,  'cost_cents' => 10000, 'notes' => 'High-margin'],
            ['key' => 'window_tint',     'name' => 'Window Tint',     'price_cents' => 40000,  'cost_cents' => 12000, 'notes' => 'High-margin'],
            ['key' => 'prep_fee',        'name' => 'Prep Fee',        'price_cents' => 25000,  'cost_cents' => 4000,  'notes' => 'Operational charge'],
            ['key' => 'trim_removal',    'name' => 'Trim Removal',    'price_cents' => 30000,  'cost_cents' => 7500,  'notes' => 'Complexity / labor offset'],
            ['key' => 'design_basic',    'name' => 'Design Basic',    'price_cents' => 30000,  'cost_cents' => 0,     'notes' => 'Simple layout'],
            ['key' => 'design_custom',   'name' => 'Design Custom',   'price_cents' => 60000,  'cost_cents' => 0,     'notes' => 'Business branding'],
            ['key' => 'design_advanced', 'name' => 'Design Advanced', 'price_cents' => 120000, 'cost_cents' => 0,     'notes' => 'Advanced graphics'],
        ];

        foreach ($addOns as $row) {
            AddOn::updateOrCreate(['key' => $row['key']], $row);
        }
    }

    /** Vehicle Data sheet, extracted to JSON (177 vehicles, placeholder row excluded). */
    private function seedVehicles(): void
    {
        $path = database_path('seeders/data/vehicles.json');
        $vehicles = json_decode(file_get_contents($path), associative: true);

        foreach ($vehicles as $row) {
            Vehicle::updateOrCreate(['name' => $row['name']], $row);
        }
    }
}
