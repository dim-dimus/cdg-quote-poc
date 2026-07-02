<?php

declare(strict_types=1);

namespace App\Livewire\FrontDesk;

use App\Models\AddOn;
use App\Models\Vehicle;
use App\Models\WrapRate;
use App\Services\QuoteRequest;
use App\Services\QuoteService;
use CDG\Pricing\ValueObjects\QuoteResult;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * The Front Desk quote screen. Inputs are bound live, so the result panel
 * recomputes on every change via QuoteService::price() (no DB write). Saving is
 * a separate action that persists a Quote via QuoteService::create().
 *
 * This component holds no pricing math — it only gathers selections and renders
 * what the engine returns.
 */
#[Layout('components.layouts.app')]
class QuoteBuilder extends Component
{
    // Vehicle combobox
    public ?int $vehicleId = null;
    public string $vehicleName = '';
    public string $vehicleSearch = '';

    // Selections
    public string $wrapTypeKey = 'color_change';
    public string $complexity = 'standard';
    public ?string $requestedFinish = null;
    public ?string $customerName = null;

    /** @var array<string, bool> add-on key => toggled on */
    public array $addonSelected = [];

    /** @var array<string, string> add-on key => override sell price in dollars */
    public array $addonOverride = [];

    public ?int $savedQuoteId = null;

    /** Editing the search box clears the current selection until one is re-picked. */
    public function updatedVehicleSearch(): void
    {
        $this->vehicleId = null;
        $this->vehicleName = '';
    }

    public function selectVehicle(int $id): void
    {
        $vehicle = Vehicle::find($id);

        if ($vehicle === null) {
            return;
        }

        $this->vehicleId = $vehicle->id;
        $this->vehicleName = $vehicle->name;
        $this->vehicleSearch = $vehicle->name;
    }

    /** @return Collection<int, Vehicle> */
    #[Computed]
    public function filteredVehicles(): Collection
    {
        $query = trim($this->vehicleSearch);

        if ($query === '' || $this->vehicleId !== null) {
            return collect();
        }

        return Vehicle::whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($query) . '%'])
            ->orderBy('category')
            ->orderBy('name')
            ->limit(30)
            ->get();
    }

    /** @return Collection<int, WrapRate> */
    #[Computed]
    public function wrapTypes(): Collection
    {
        return WrapRate::orderBy('name')->get();
    }

    /** @return Collection<int, AddOn> */
    #[Computed]
    public function addOns(): Collection
    {
        return AddOn::orderBy('name')->get();
    }

    /** @return array<string, string> */
    public function complexityOptions(): array
    {
        return [
            'easy'      => 'Easy',
            'standard'  => 'Standard',
            'complex'   => 'Complex',
            'specialty' => 'Specialty',
        ];
    }

    /** Live result for the current selections, or null if none can be priced yet. */
    #[Computed]
    public function result(): ?QuoteResult
    {
        $request = $this->buildRequest();

        if ($request === null) {
            return null;
        }

        try {
            return app(QuoteService::class)->price($request);
        } catch (\Throwable) {
            return null;
        }
    }

    public function save(): void
    {
        $this->validate([
            'vehicleId'        => 'required|integer',
            'wrapTypeKey'      => 'required|string',
            'complexity'       => 'required|string',
            'addonOverride.*'  => 'nullable|numeric|min:0',
            'customerName'     => 'nullable|string|max:255',
            'requestedFinish'  => 'nullable|string|max:255',
        ], [], ['vehicleId' => 'vehicle']);

        $request = $this->buildRequest();

        if ($request === null) {
            return;
        }

        $quote = app(QuoteService::class)->create($request, auth()->id());

        $this->savedQuoteId = $quote->id;
        session()->flash('saved', "Quote #{$quote->id} saved.");
    }

    private function buildRequest(): ?QuoteRequest
    {
        if ($this->vehicleId === null) {
            return null;
        }

        $selections = [];
        foreach ($this->addonSelected as $key => $on) {
            if (! $on) {
                continue;
            }
            $override = $this->addonOverride[$key] ?? null;
            $selections[$key] = ($override === null || $override === '')
                ? null
                : (int) round(((float) $override) * 100);
        }

        return new QuoteRequest(
            vehicleId:       $this->vehicleId,
            wrapTypeKey:     $this->wrapTypeKey,
            complexity:      $this->complexity,
            addOnSelections: $selections,
            requestedFinish: $this->requestedFinish ?: null,
            customerName:    $this->customerName ?: null,
        );
    }

    public function render()
    {
        return view('livewire.front-desk.quote-builder');
    }
}
