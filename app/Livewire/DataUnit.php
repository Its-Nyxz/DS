<?php

namespace App\Livewire;

use App\Models\Unit;
use Livewire\Component;
use Illuminate\Support\Str;
use Livewire\WithPagination;

class DataUnit extends Component
{
    use WithPagination;

    public $search = '';
    public $unitId;
    public $name, $symbol;
    public $isModalOpen = false;

    protected $rules = [
        'name' => 'required|string|max:255',
        'symbol' => 'required|string|max:10',
    ];

    public function render()
    {
        $units = Unit::when(
            $this->search,
            fn($q) =>
            $q->where('name', 'like', "%{$this->search}%")
        )
            ->orderBy('name')
            ->paginate(10);

        return view('livewire.data-unit', compact('units'));
    }

    public function openModal()
    {
        $this->resetForm();
        $this->isModalOpen = true;
    }

    public function edit($id)
    {
        $unit = Unit::findOrFail($id);
        $this->unitId = $unit->id;
        $this->name = $unit->name;
        $this->symbol = $unit->symbol;
        $this->isModalOpen = true;
    }

    public function save()
    {
        $this->validate();

        $slug = Str::slug($this->name);

        Unit::updateOrCreate(
            ['id' => $this->unitId],
            ['name' => $this->name, 'slug' => $slug, 'symbol' => $this->symbol]
        );

        $this->dispatch('alert-success', ['message' => 'Data berhasil disimpan.']);
        $this->resetForm();
    }

    public function delete($id)
    {
        $unit = Unit::findOrFail($id);

        // Cek apakah Unit digunakan di model Item
        if ($unit->items()->exists()) {
            $this->dispatch('alert-error', ['message' => 'Satuan tidak dapat dihapus karena masih digunakan di data barang.']);
            return;
        }

        $unit->delete();
        $this->dispatch('alert-success', ['message' => 'Data satuan berhasil dihapus.']);
    }

    public function resetForm()
    {
        $this->reset(['unitId', 'name', 'symbol', 'isModalOpen']);
    }
}
