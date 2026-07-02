<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Quote;
use App\Models\Vehicle;
use App\Models\WrapRate;
use App\Repositories\PricingConfigRepository;
use CDG\Pricing\Engine;
use CDG\Pricing\Modules\Wrap\WrapInput;
use CDG\Pricing\ValueObjects\BreakdownMetric;
use CDG\Pricing\ValueObjects\PricingConfig;
use CDG\Pricing\ValueObjects\QuoteResult;
use CDG\Pricing\ValueObjects\ServiceLine;

/**
 * The single place where the Laravel app meets the pricing engine.
 *
 * It resolves a QuoteRequest against the database (vehicle + wrap rate + config),
 * runs the pure engine, and persists the result as a Quote. No pricing math
 * happens here — that all lives in packages/pricing.
 *
 * A saved Quote is an immutable record: alongside the totals it freezes the
 * resolved inputs and the pricing config used, so it reproduces to the cent even
 * after admins later edit vehicles or config (Phase 6).
 */
final class QuoteService
{
    public function __construct(
        private readonly Engine $engine,
        private readonly PricingConfigRepository $configRepository,
    ) {}

    /**
     * Price the request through the engine and persist a Quote snapshot.
     *
     * @param  int|null  $userId  The staff member creating the quote, if known.
     */
    public function create(QuoteRequest $request, ?int $userId = null): Quote
    {
        $vehicle = Vehicle::findOrFail($request->vehicleId);
        $wrapRate = $this->wrapRate($request->wrapTypeKey);
        $config = $this->configRepository->load();
        $input = $this->buildInput($request, $vehicle, $wrapRate);

        $result = $this->engine->run(['wrap' => $input], $config);

        return Quote::create([
            'user_id' => $userId,
            'customer_name' => $request->customerName,
            'vehicle_id' => $vehicle->id,
            'wrap_rate_id' => $wrapRate->id,
            'complexity' => $request->complexity,
            'requested_finish' => $request->requestedFinish,
            'add_on_selections' => $request->addOnSelections,
            'total_sell_cents' => $result->totalSellCents,
            'total_cost_cents' => $result->totalCostCents,
            'gross_profit_cents' => $result->grossProfitCents,
            'gross_margin' => $result->grossMargin,
            'decision' => $result->decision,
            'lines' => $this->serializeLines($result->lines),
            'breakdown' => $this->serializeBreakdown($result->breakdown),
            'input_snapshot' => $this->inputSnapshot($vehicle, $wrapRate, $input),
            'config_snapshot' => $this->configSnapshot($config),
        ]);
    }

    /**
     * Run the engine for a request without persisting — used for live preview.
     */
    public function price(QuoteRequest $request): QuoteResult
    {
        $vehicle = Vehicle::findOrFail($request->vehicleId);
        $wrapRate = $this->wrapRate($request->wrapTypeKey);
        $input = $this->buildInput($request, $vehicle, $wrapRate);

        return $this->engine->run(['wrap' => $input], $this->configRepository->load());
    }

    private function buildInput(QuoteRequest $request, Vehicle $vehicle, WrapRate $wrapRate): WrapInput
    {
        return new WrapInput(
            laborLowHours: $vehicle->labor_low_hours,
            laborHighHours: $vehicle->labor_high_hours,
            sqFtLow: $vehicle->sqft_low,
            sqFtHigh: $vehicle->sqft_high,
            rateLowCents: $wrapRate->rate_low_cents,
            rateHighCents: $wrapRate->rate_high_cents,
            complexity: $request->complexity,
            addOnSelections: $request->addOnSelections,
            requestedFinish: $request->requestedFinish,
        );
    }

    private function wrapRate(string $key): WrapRate
    {
        return WrapRate::where('key', $key)->firstOrFail();
    }

    /**
     * @param  ServiceLine[]  $lines
     * @return array<int, array{serviceType: string, description: string, sellCents: int, costCents: int}>
     */
    private function serializeLines(array $lines): array
    {
        return array_map(fn (ServiceLine $line) => [
            'serviceType' => $line->serviceType,
            'description' => $line->description,
            'sellCents' => $line->sellCents,
            'costCents' => $line->costCents,
        ], $lines);
    }

    /**
     * @param  array<string, BreakdownMetric[]>  $breakdown  serviceType => metrics
     * @return array<string, array<int, array{label: string, value: int|float, unit: string}>>
     */
    private function serializeBreakdown(array $breakdown): array
    {
        return array_map(
            fn (array $metrics) => array_map(
                fn (BreakdownMetric $metric) => [
                    'label' => $metric->label,
                    'value' => $metric->value,
                    'unit' => $metric->unit,
                ],
                $metrics,
            ),
            $breakdown,
        );
    }

    /**
     * Freeze the resolved inputs (with display names) behind this quote so it
     * stays reproducible even if the vehicle or wrap rate is edited later.
     *
     * @return array<string, mixed>
     */
    private function inputSnapshot(Vehicle $vehicle, WrapRate $wrapRate, WrapInput $input): array
    {
        return [
            'vehicleId' => $vehicle->id,
            'vehicleName' => $vehicle->name,
            'vehicleCategory' => $vehicle->category,
            'wrapTypeKey' => $wrapRate->key,
            'wrapTypeName' => $wrapRate->name,
            'laborLowHours' => $input->laborLowHours,
            'laborHighHours' => $input->laborHighHours,
            'sqFtLow' => $input->sqFtLow,
            'sqFtHigh' => $input->sqFtHigh,
            'rateLowCents' => $input->rateLowCents,
            'rateHighCents' => $input->rateHighCents,
            'complexity' => $input->complexity,
            'requestedFinish' => $input->requestedFinish,
            'addOnSelections' => $input->addOnSelections,
        ];
    }

    /**
     * Freeze the admin-editable pricing knobs used to price this quote.
     *
     * @return array<string, mixed>
     */
    private function configSnapshot(PricingConfig $config): array
    {
        return [
            'shopRateCents' => $config->shopRateCents,
            'wasteMultiplier' => $config->wasteMultiplier,
            'materialCostCentsPerSqFt' => $config->materialCostCentsPerSqFt,
            'complexityMultipliers' => $config->complexityMultipliers,
            'marginFloors' => $config->marginFloors,
            'addOns' => $config->addOns,
        ];
    }
}
