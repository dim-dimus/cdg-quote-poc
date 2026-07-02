<div class="grid grid-cols-1 gap-6 lg:grid-cols-5">
    {{-- ── Inputs ─────────────────────────────────────────────────────────── --}}
    <div class="space-y-6 lg:col-span-3">
        <div class="rounded-lg border border-slate-200 bg-white p-5">
            <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-500">Vehicle &amp; wrap</h2>

            {{-- Vehicle combobox --}}
            <div class="relative" wire:key="vehicle-combobox">
                <label for="vehicleSearch" class="block text-sm font-medium text-slate-700">Vehicle</label>
                <input id="vehicleSearch" type="text" autocomplete="off"
                       wire:model.live.debounce.200ms="vehicleSearch"
                       placeholder="Search 177 vehicles…"
                       class="mt-1 w-full rounded border border-slate-300 px-3 py-2 focus:border-slate-500 focus:outline-none">

                @if ($this->filteredVehicles->isNotEmpty())
                    <ul class="absolute z-10 mt-1 max-h-64 w-full overflow-auto rounded border border-slate-200 bg-white shadow-lg">
                        @foreach ($this->filteredVehicles as $vehicle)
                            <li>
                                <button type="button" wire:click="selectVehicle({{ $vehicle->id }})"
                                        class="flex w-full items-center justify-between px-3 py-2 text-left hover:bg-slate-50">
                                    <span>{{ $vehicle->name }}</span>
                                    <span class="text-xs text-slate-400">{{ $vehicle->category }}</span>
                                </button>
                            </li>
                        @endforeach
                    </ul>
                @endif

                @if ($vehicleId)
                    <p class="mt-1 text-xs text-green-700">Selected: {{ $vehicleName }}</p>
                @endif
                @error('vehicleId') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- Wrap type --}}
            <div class="mt-4">
                <span class="block text-sm font-medium text-slate-700">Wrap type</span>
                <div class="mt-1 flex flex-wrap gap-2">
                    @foreach ($this->wrapTypes as $wrapType)
                        <label class="cursor-pointer rounded border px-3 py-2 text-sm
                                      {{ $wrapTypeKey === $wrapType->key ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-300 hover:bg-slate-50' }}">
                            <input type="radio" class="sr-only" wire:model.live="wrapTypeKey" value="{{ $wrapType->key }}">
                            {{ $wrapType->name }}
                        </label>
                    @endforeach
                </div>
            </div>

            {{-- Complexity --}}
            <div class="mt-4">
                <span class="block text-sm font-medium text-slate-700">Complexity</span>
                <div class="mt-1 flex flex-wrap gap-2">
                    @foreach ($this->complexityOptions() as $key => $label)
                        <label class="cursor-pointer rounded border px-3 py-2 text-sm
                                      {{ $complexity === $key ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-300 hover:bg-slate-50' }}">
                            <input type="radio" class="sr-only" wire:model.live="complexity" value="{{ $key }}">
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
            </div>

            {{-- Requested finish (informational, D9) --}}
            <div class="mt-4">
                <label for="requestedFinish" class="block text-sm font-medium text-slate-700">
                    Requested finish <span class="text-slate-400">(optional, no price effect)</span>
                </label>
                <input id="requestedFinish" type="text" wire:model="requestedFinish"
                       placeholder="e.g. Matte, Satin, Gloss"
                       class="mt-1 w-full rounded border border-slate-300 px-3 py-2 focus:border-slate-500 focus:outline-none">
            </div>
        </div>

        {{-- Add-ons --}}
        <div class="rounded-lg border border-slate-200 bg-white p-5">
            <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-500">Add-ons</h2>
            <div class="space-y-3">
                @foreach ($this->addOns as $addOn)
                    <div wire:key="addon-{{ $addOn->key }}" class="flex items-center justify-between gap-4">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" wire:model.live="addonSelected.{{ $addOn->key }}"
                                   class="rounded border-slate-300">
                            <span>{{ $addOn->name }}</span>
                            <span class="text-xs text-slate-400">default <x-money :cents="$addOn->price_cents" /></span>
                        </label>

                        @if (($addonSelected[$addOn->key] ?? false))
                            <div class="flex items-center gap-1">
                                <span class="text-sm text-slate-400">$</span>
                                <input type="number" step="0.01" min="0"
                                       wire:model.live.debounce.300ms="addonOverride.{{ $addOn->key }}"
                                       placeholder="{{ number_format($addOn->price_cents / 100, 2) }}"
                                       class="w-24 rounded border border-slate-300 px-2 py-1 text-right text-sm focus:border-slate-500 focus:outline-none">
                                @error('addonOverride.' . $addOn->key)
                                    <span class="text-xs text-red-600">{{ $message }}</span>
                                @enderror
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
            <p class="mt-3 text-xs text-slate-400">Override a sell price per quote; cost always comes from the catalog (D7).</p>
        </div>

        {{-- Customer --}}
        <div class="rounded-lg border border-slate-200 bg-white p-5">
            <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-500">Customer</h2>
            <label for="customerName" class="block text-sm font-medium text-slate-700">Name (optional)</label>
            <input id="customerName" type="text" wire:model="customerName"
                   class="mt-1 w-full rounded border border-slate-300 px-3 py-2 focus:border-slate-500 focus:outline-none">
        </div>
    </div>

    {{-- ── Result panel ───────────────────────────────────────────────────── --}}
    <div class="lg:col-span-2">
        <div class="sticky top-6 rounded-lg border border-slate-200 bg-white p-5">
            <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-500">Quote</h2>

            @if (session('saved'))
                <div class="mb-4 rounded bg-green-50 px-3 py-2 text-sm text-green-800">{{ session('saved') }}</div>
            @endif

            @if ($this->result)
                @php $r = $this->result; @endphp

                {{-- Pricing engine diagnostics — mirrors the workbook's
                     PRICING + DECISION ENGINE panel, in the same row order.
                     Values come straight from the engine; no math here. --}}
                @foreach ($r->breakdown as $metrics)
                    @if (! empty($metrics))
                        <details class="mb-4 rounded border border-slate-200" open>
                            <summary class="cursor-pointer px-3 py-2 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Pricing engine
                            </summary>
                            <table class="w-full text-sm">
                                <tbody class="divide-y divide-slate-100">
                                    @foreach ($metrics as $metric)
                                        <tr>
                                            <td class="px-3 py-1.5 text-slate-600">{{ $metric->label }}</td>
                                            <td class="px-3 py-1.5 text-right tabular-nums">
                                                @switch($metric->unit)
                                                    @case('cents')
                                                        <x-money :cents="(int) $metric->value" />
                                                        @break
                                                    @case('hours')
                                                        {{ number_format((float) $metric->value, 1) }}
                                                        @break
                                                    @default
                                                        {{ number_format((float) $metric->value, 0) }}
                                                @endswitch
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </details>
                    @endif
                @endforeach

                <table class="w-full text-sm">
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($r->lines as $line)
                            <tr>
                                <td class="py-2 text-slate-600">{{ $line->description }}</td>
                                <td class="py-2 text-right"><x-money :cents="$line->sellCents" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="border-t-2 border-slate-200">
                        <tr class="font-semibold">
                            <td class="pt-3">Total sell</td>
                            <td class="pt-3 text-right" data-test="total-sell"><x-money :cents="$r->totalSellCents" /></td>
                        </tr>
                        <tr class="text-slate-500">
                            <td class="py-1">Total cost</td>
                            <td class="py-1 text-right" data-test="total-cost"><x-money :cents="$r->totalCostCents" /></td>
                        </tr>
                        <tr>
                            <td class="py-1">Gross profit</td>
                            <td class="py-1 text-right" data-test="gross-profit"><x-money :cents="$r->grossProfitCents" /></td>
                        </tr>
                        <tr>
                            <td class="py-1">Gross margin</td>
                            <td class="py-1 text-right" data-test="gross-margin">{{ number_format($r->grossMargin * 100, 1) }}%</td>
                        </tr>
                    </tfoot>
                </table>

                @php
                    $decisionClasses = match ($r->decision) {
                        'REJECT / REPRICE' => 'bg-red-100 text-red-800',
                        'REVIEW'           => 'bg-amber-100 text-amber-800',
                        'GOOD'             => 'bg-green-100 text-green-800',
                        'STRONG'           => 'bg-emerald-100 text-emerald-800',
                        default            => 'bg-slate-100 text-slate-700',
                    };
                @endphp
                <div class="mt-4 rounded px-3 py-2 text-center text-sm font-semibold {{ $decisionClasses }}"
                     data-test="decision">
                    {{ $r->decision }}
                </div>

                <button type="button" wire:click="save"
                        class="mt-4 w-full rounded bg-slate-900 py-2 font-medium text-white hover:bg-slate-800">
                    Save quote
                </button>
            @else
                <p class="text-sm text-slate-400">Select a vehicle to see pricing.</p>
            @endif
        </div>
    </div>
</div>
