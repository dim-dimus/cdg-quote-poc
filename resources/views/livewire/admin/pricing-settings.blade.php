<div class="mx-auto max-w-3xl">
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-lg font-semibold">Pricing settings</h1>
        <a href="{{ route('front-desk') }}" class="text-sm text-slate-500 hover:text-slate-900">← Front Desk</a>
    </div>

    @if (session('saved'))
        <div class="mb-4 rounded bg-green-50 px-3 py-2 text-sm text-green-800">{{ session('saved') }}</div>
    @endif

    <form wire:submit="save" class="space-y-6">
        {{-- Shop settings --}}
        <section class="rounded-lg border border-slate-200 bg-white p-5">
            <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-500">Shop settings</h2>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-admin.field label="Shop rate ($/hr)" model="shopRate" prefix="$" />
                <x-admin.field label="Material cost ($/sq ft)" model="materialCost" prefix="$" />
                <x-admin.field label="Waste multiplier" model="wasteMultiplier" />
                <div></div>
                <x-admin.field label="Complexity · Easy" model="complexityEasy" />
                <x-admin.field label="Complexity · Standard" model="complexityStandard" />
                <x-admin.field label="Complexity · Complex" model="complexityComplex" />
                <x-admin.field label="Complexity · Specialty" model="complexitySpecialty" />
                <x-admin.field label="Margin floor · Reject" model="marginReject" />
                <x-admin.field label="Margin floor · Review" model="marginReview" />
                <x-admin.field label="Margin floor · Strong" model="marginStrong" />
            </div>
        </section>

        {{-- Wrap rates --}}
        <section class="rounded-lg border border-slate-200 bg-white p-5">
            <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-500">Wrap rates ($/sq ft)</h2>
            <div class="space-y-4">
                @foreach ($wrapRates as $id => $row)
                    <div wire:key="wrap-{{ $id }}" class="grid grid-cols-1 items-end gap-4 sm:grid-cols-3">
                        <div class="text-sm font-medium text-slate-700">{{ $row['name'] }}</div>
                        <x-admin.field label="Rate low" model="wrapRates.{{ $id }}.low" prefix="$" />
                        <x-admin.field label="Rate high" model="wrapRates.{{ $id }}.high" prefix="$" />
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Add-ons --}}
        <section class="rounded-lg border border-slate-200 bg-white p-5">
            <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-500">Add-ons ($)</h2>
            <div class="space-y-4">
                @foreach ($addOns as $id => $row)
                    <div wire:key="addon-{{ $id }}" class="grid grid-cols-1 items-end gap-4 sm:grid-cols-3">
                        <div class="text-sm font-medium text-slate-700">{{ $row['name'] }}</div>
                        <x-admin.field label="Sell price" model="addOns.{{ $id }}.price" prefix="$" />
                        <x-admin.field label="Cost" model="addOns.{{ $id }}.cost" prefix="$" />
                    </div>
                @endforeach
            </div>
        </section>

        <div class="flex justify-end">
            <button type="submit" class="rounded bg-slate-900 px-4 py-2 font-medium text-white hover:bg-slate-800">
                Save settings
            </button>
        </div>
    </form>
</div>
