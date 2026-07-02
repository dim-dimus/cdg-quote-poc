<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\AddOn;
use App\Models\ShopSetting;
use App\Models\WrapRate;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Admin: edit the pricing knobs that PricingConfigRepository reads into the
 * engine's PricingConfig. Saving writes the config tables (shop_settings,
 * wrap_rates, add_ons); the very next quote prices with the new values — no
 * deploy. This screen holds no pricing math; it only reads and writes config.
 *
 * Money is edited in dollars and converted to integer cents at the boundary via
 * Support\Money. Multipliers and margin floors are plain decimals.
 */
#[Layout('components.layouts.app')]
class PricingSettings extends Component
{
    // Shop settings (money fields are dollars; the rest are decimals).
    public string $shopRate = '';            // $/hr

    public string $materialCost = '';        // $/sq ft

    public string $wasteMultiplier = '';

    public string $complexityEasy = '';

    public string $complexityStandard = '';

    public string $complexityComplex = '';

    public string $complexitySpecialty = '';

    public string $marginReject = '';

    public string $marginReview = '';

    public string $marginStrong = '';

    /** @var array<int, array{name: string, low: string, high: string}> keyed by wrap_rate id (dollars) */
    public array $wrapRates = [];

    /** @var array<int, array{name: string, price: string, cost: string}> keyed by add_on id (dollars) */
    public array $addOns = [];

    public function mount(): void
    {
        $s = ShopSetting::query()->pluck('value', 'key');

        $this->shopRate = Money::forInput((int) round($s['shop_rate_cents']));
        $this->materialCost = Money::forInput((int) round($s['material_cost_cents_per_sqft']));
        $this->wasteMultiplier = (string) $s['waste_multiplier'];
        $this->complexityEasy = (string) $s['complexity_multiplier_easy'];
        $this->complexityStandard = (string) $s['complexity_multiplier_standard'];
        $this->complexityComplex = (string) $s['complexity_multiplier_complex'];
        $this->complexitySpecialty = (string) $s['complexity_multiplier_specialty'];
        $this->marginReject = (string) $s['margin_floor_reject'];
        $this->marginReview = (string) $s['margin_floor_review'];
        $this->marginStrong = (string) $s['margin_floor_strong'];

        $this->wrapRates = WrapRate::orderBy('name')->get()
            ->mapWithKeys(fn (WrapRate $r) => [$r->id => [
                'name' => $r->name,
                'low' => Money::forInput($r->rate_low_cents),
                'high' => Money::forInput($r->rate_high_cents),
            ]])->all();

        $this->addOns = AddOn::orderBy('name')->get()
            ->mapWithKeys(fn (AddOn $a) => [$a->id => [
                'name' => $a->name,
                'price' => Money::forInput($a->price_cents),
                'cost' => Money::forInput($a->cost_cents),
            ]])->all();
    }

    /** @return array<string, mixed> */
    protected function rules(): array
    {
        return [
            'shopRate' => ['required', 'numeric', 'min:0'],
            'materialCost' => ['required', 'numeric', 'min:0'],
            'wasteMultiplier' => ['required', 'numeric', 'min:0'],
            'complexityEasy' => ['required', 'numeric', 'gt:0'],
            'complexityStandard' => ['required', 'numeric', 'gt:0'],
            'complexityComplex' => ['required', 'numeric', 'gt:0'],
            'complexitySpecialty' => ['required', 'numeric', 'gt:0'],
            'marginReject' => ['required', 'numeric', 'between:0,1'],
            'marginReview' => ['required', 'numeric', 'between:0,1'],
            'marginStrong' => ['required', 'numeric', 'between:0,1'],
            'wrapRates.*.low' => ['required', 'numeric', 'min:0'],
            'wrapRates.*.high' => ['required', 'numeric', 'min:0'],
            'addOns.*.price' => ['required', 'numeric', 'min:0'],
            'addOns.*.cost' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function save(): void
    {
        $this->validate();

        // Decision floors must ascend: reject < review < strong (DECISIONS D6).
        if (! ((float) $this->marginReject < (float) $this->marginReview
            && (float) $this->marginReview < (float) $this->marginStrong)) {
            $this->addError('marginReview', 'Margin floors must ascend: reject < review < strong.');

            return;
        }

        DB::transaction(function () {
            $this->putSetting('shop_rate_cents', Money::toCents($this->shopRate));
            $this->putSetting('material_cost_cents_per_sqft', Money::toCents($this->materialCost));
            $this->putSetting('waste_multiplier', (float) $this->wasteMultiplier);
            $this->putSetting('complexity_multiplier_easy', (float) $this->complexityEasy);
            $this->putSetting('complexity_multiplier_standard', (float) $this->complexityStandard);
            $this->putSetting('complexity_multiplier_complex', (float) $this->complexityComplex);
            $this->putSetting('complexity_multiplier_specialty', (float) $this->complexitySpecialty);
            $this->putSetting('margin_floor_reject', (float) $this->marginReject);
            $this->putSetting('margin_floor_review', (float) $this->marginReview);
            $this->putSetting('margin_floor_strong', (float) $this->marginStrong);

            foreach ($this->wrapRates as $id => $row) {
                WrapRate::whereKey($id)->update([
                    'rate_low_cents' => Money::toCents($row['low']),
                    'rate_high_cents' => Money::toCents($row['high']),
                ]);
            }

            foreach ($this->addOns as $id => $row) {
                AddOn::whereKey($id)->update([
                    'price_cents' => Money::toCents($row['price']),
                    'cost_cents' => Money::toCents($row['cost']),
                ]);
            }
        });

        session()->flash('saved', 'Pricing settings saved. New quotes use these values immediately.');

        // Bring the success banner (top of the page) into view after saving.
        $this->dispatch('scroll-to-top');
    }

    private function putSetting(string $key, int|float $value): void
    {
        ShopSetting::where('key', $key)->update(['value' => $value]);
    }

    public function render()
    {
        return view('livewire.admin.pricing-settings');
    }
}
