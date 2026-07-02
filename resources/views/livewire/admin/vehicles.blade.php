<div class="mx-auto max-w-4xl">
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-lg font-semibold">Vehicles</h1>
        <a href="{{ route('front-desk') }}" class="text-sm text-slate-500 hover:text-slate-900">← Front Desk</a>
    </div>

    @if (session('saved'))
        <div class="mb-4 rounded bg-green-50 px-3 py-2 text-sm text-green-800">{{ session('saved') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded bg-red-50 px-3 py-2 text-sm text-red-800">{{ session('error') }}</div>
    @endif

    {{-- Add / edit form --}}
    @if ($showForm)
        <div class="mb-6 rounded-lg border border-slate-200 bg-white p-5">
            <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-500">
                {{ $editingId ? 'Edit vehicle' : 'New vehicle' }}
            </h2>
            <form wire:submit="save" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <x-admin.field label="Name" model="name" />
                <x-admin.field label="Category" model="category" />
                <x-admin.field label="Labor low (hrs)" model="laborLow" />
                <x-admin.field label="Labor high (hrs)" model="laborHigh" />
                <x-admin.field label="Sq ft low" model="sqftLow" />
                <x-admin.field label="Sq ft high" model="sqftHigh" />
                <div class="sm:col-span-2">
                    <x-admin.field label="Notes (optional)" model="notes" />
                </div>
                <div class="flex gap-2 sm:col-span-2">
                    <button type="submit" class="rounded bg-slate-900 px-4 py-2 font-medium text-white hover:bg-slate-800">
                        {{ $editingId ? 'Update' : 'Add' }} vehicle
                    </button>
                    <button type="button" wire:click="cancel" class="rounded border border-slate-300 px-4 py-2 hover:bg-slate-50">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    @else
        <div class="mb-4 flex items-center gap-3">
            <input type="text" wire:model.live.debounce.200ms="search" placeholder="Search vehicles…"
                   class="w-full rounded border border-slate-300 px-3 py-2 focus:border-slate-500 focus:outline-none">
            <button type="button" wire:click="create"
                    class="whitespace-nowrap rounded bg-slate-900 px-4 py-2 font-medium text-white hover:bg-slate-800">
                + New vehicle
            </button>
        </div>
    @endif

    {{-- List --}}
    <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-2">Name</th>
                    <th class="px-4 py-2">Category</th>
                    <th class="px-4 py-2 text-right">Labor (hrs)</th>
                    <th class="px-4 py-2 text-right">Sq ft</th>
                    <th class="px-4 py-2"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($this->vehicles as $vehicle)
                    <tr wire:key="veh-{{ $vehicle->id }}">
                        <td class="px-4 py-2 font-medium text-slate-800">{{ $vehicle->name }}</td>
                        <td class="px-4 py-2 text-slate-500">{{ $vehicle->category }}</td>
                        <td class="px-4 py-2 text-right tabular-nums">{{ $vehicle->labor_low_hours }}–{{ $vehicle->labor_high_hours }}</td>
                        <td class="px-4 py-2 text-right tabular-nums">{{ $vehicle->sqft_low }}–{{ $vehicle->sqft_high }}</td>
                        <td class="px-4 py-2 text-right">
                            <button type="button" wire:click="edit({{ $vehicle->id }})" class="text-slate-600 hover:text-slate-900">Edit</button>
                            <button type="button" wire:click="delete({{ $vehicle->id }})"
                                    wire:confirm="Remove {{ $vehicle->name }}?"
                                    class="ml-3 text-red-600 hover:text-red-800">Delete</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-6 text-center text-slate-400">No vehicles match.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
