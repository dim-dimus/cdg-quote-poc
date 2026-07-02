<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\Quote;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Admin: manage the vehicle catalog (labor-hour and surface-area ranges) that
 * quotes are built from. Add / edit / remove; a vehicle referenced by an
 * existing quote cannot be deleted (quotes freeze their own input snapshot, but
 * the FK is still restricted to keep the catalog referentially clean).
 *
 * No pricing math here — vehicles are plain reference data.
 */
#[Layout('components.layouts.app')]
class Vehicles extends Component
{
    public string $search = '';

    public bool $showForm = false;

    public ?int $editingId = null;

    // Form fields
    public string $name = '';

    public string $category = '';

    public string $laborLow = '';

    public string $laborHigh = '';

    public string $sqftLow = '';

    public string $sqftHigh = '';

    public string $notes = '';

    /** @return Collection<int, Vehicle> */
    #[Computed]
    public function vehicles(): Collection
    {
        $query = trim($this->search);

        return Vehicle::query()
            ->when($query !== '', fn (Builder $q) => $q->whereRaw('LOWER(name) LIKE ?', ['%'.strtolower($query).'%']))
            ->orderBy('category')
            ->orderBy('name')
            ->limit(60)
            ->get();
    }

    public function create(): void
    {
        $this->reset('editingId', 'name', 'category', 'laborLow', 'laborHigh', 'sqftLow', 'sqftHigh', 'notes');
        $this->resetValidation();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $vehicle = Vehicle::findOrFail($id);

        $this->editingId = $vehicle->id;
        $this->name = $vehicle->name;
        $this->category = $vehicle->category;
        $this->laborLow = (string) $vehicle->labor_low_hours;
        $this->laborHigh = (string) $vehicle->labor_high_hours;
        $this->sqftLow = (string) $vehicle->sqft_low;
        $this->sqftHigh = (string) $vehicle->sqft_high;
        $this->notes = (string) $vehicle->notes;
        $this->resetValidation();
        $this->showForm = true;
    }

    public function cancel(): void
    {
        $this->showForm = false;
        $this->resetValidation();
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('vehicles', 'name')->ignore($this->editingId)],
            'category' => ['required', 'string', 'max:255'],
            'laborLow' => ['required', 'numeric', 'min:0'],
            'laborHigh' => ['required', 'numeric', 'gte:laborLow'],
            'sqftLow' => ['required', 'integer', 'min:0'],
            'sqftHigh' => ['required', 'integer', 'gte:sqftLow'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        Vehicle::updateOrCreate(
            ['id' => $this->editingId],
            [
                'name' => $data['name'],
                'category' => $data['category'],
                'labor_low_hours' => (float) $data['laborLow'],
                'labor_high_hours' => (float) $data['laborHigh'],
                'sqft_low' => (int) $data['sqftLow'],
                'sqft_high' => (int) $data['sqftHigh'],
                'notes' => $data['notes'] ?: null,
            ],
        );

        $this->showForm = false;
        session()->flash('saved', $this->editingId ? 'Vehicle updated.' : 'Vehicle added.');
    }

    public function delete(int $id): void
    {
        if (Quote::where('vehicle_id', $id)->exists()) {
            session()->flash('error', 'Cannot delete a vehicle used by existing quotes.');

            return;
        }

        Vehicle::whereKey($id)->delete();
        session()->flash('saved', 'Vehicle removed.');
    }

    public function render()
    {
        return view('livewire.admin.vehicles');
    }
}
