<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Quote;
use App\Models\Vehicle;
use App\Models\WrapRate;
use App\Repositories\PricingConfigRepository;
use CDG\Pricing\Engine;
use CDG\Pricing\Modules\Wrap\WrapInput;
use CDG\Pricing\ValueObjects\QuoteResult;
use CDG\Pricing\ValueObjects\ServiceLine;

/**
 * The single place where the Laravel app meets the pricing engine.
 *
 * It resolves a QuoteRequest against the database (vehicle + wrap rate + config),
 * runs the pure engine, and persists the result as a Quote. No pricing math
 * happens here — that all lives in packages/pricing.
 */
final class QuoteService
{
    public function __construct(
        private readonly Engine $engine,
        private readonly PricingConfigRepository $configRepository,
    ) {
    }

    /**
     * Price the request through the engine and persist a Quote snapshot.
     */
    public function create(QuoteRequest $request): Quote
    {
        $result = $this->price($request);

        return Quote::create([
            'customer_name'      => $request->customerName,
            'vehicle_id'         => $request->vehicleId,
            'wrap_rate_id'       => $this->wrapRate($request->wrapTypeKey)->id,
            'complexity'         => $request->complexity,
            'requested_finish'   => $request->requestedFinish,
            'add_on_selections'  => $request->addOnSelections,
            'total_sell_cents'   => $result->totalSellCents,
            'total_cost_cents'   => $result->totalCostCents,
            'gross_profit_cents' => $result->grossProfitCents,
            'gross_margin'       => $result->grossMargin,
            'decision'           => $result->decision,
            'lines'              => $this->serializeLines($result->lines),
        ]);
    }

    /**
     * Run the engine for a request without persisting — used for live preview.
     */
    public function price(QuoteRequest $request): QuoteResult
    {
        $vehicle  = Vehicle::findOrFail($request->vehicleId);
        $wrapRate = $this->wrapRate($request->wrapTypeKey);

        $input = new WrapInput(
            laborLowHours:   $vehicle->labor_low_hours,
            laborHighHours:  $vehicle->labor_high_hours,
            sqFtLow:         $vehicle->sqft_low,
            sqFtHigh:        $vehicle->sqft_high,
            rateLowCents:    $wrapRate->rate_low_cents,
            rateHighCents:   $wrapRate->rate_high_cents,
            complexity:      $request->complexity,
            addOnSelections: $request->addOnSelections,
            requestedFinish: $request->requestedFinish,
        );

        return $this->engine->run(['wrap' => $input], $this->configRepository->load());
    }

    private function wrapRate(string $key): WrapRate
    {
        return WrapRate::where('key', $key)->firstOrFail();
    }

    /**
     * @param ServiceLine[] $lines
     * @return array<int, array{serviceType: string, description: string, sellCents: int, costCents: int}>
     */
    private function serializeLines(array $lines): array
    {
        return array_map(fn (ServiceLine $line) => [
            'serviceType' => $line->serviceType,
            'description' => $line->description,
            'sellCents'   => $line->sellCents,
            'costCents'   => $line->costCents,
        ], $lines);
    }
}
