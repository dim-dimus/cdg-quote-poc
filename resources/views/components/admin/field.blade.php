@props([
    'label',
    'model',
    'prefix' => null,
])

<div>
    <label class="block text-sm font-medium text-slate-700">{{ $label }}</label>
    <div class="mt-1 flex items-center gap-1">
        @if ($prefix)
            <span class="text-sm text-slate-400">{{ $prefix }}</span>
        @endif
        <input type="text" inputmode="decimal" wire:model="{{ $model }}"
               class="w-full rounded border border-slate-300 px-2 py-1.5 text-right tabular-nums focus:border-slate-500 focus:outline-none">
    </div>
    @error($model)
        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
    @enderror
</div>
