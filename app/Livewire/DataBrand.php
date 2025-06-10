<?php

namespace App\Livewire;

use App\Models\Brand;
use Livewire\Component;
use Illuminate\Support\Str;
use Livewire\WithPagination;

class DataBrand extends Component
{
    use WithPagination;

    public $name, $brandId;
    public $isModalOpen = false;
    public $search = '';

    public $orderBy = 'created_at';
    public $orderDirection = 'desc';

    protected $listeners = ['refreshDataBrand' => 'render'];

    public function render()
    {
        $brands = Brand::query()
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->orderBy($this->orderBy, $this->orderDirection)
            ->paginate(9);

        return view('livewire.data-brand', compact('brands'));
    }

    public function openModal()
    {
        $this->resetForm();
        $this->isModalOpen = true;
    }

    public function edit($id)
    {
        $brand = Brand::findOrFail($id);
        $this->brandId = $brand->id;
        $this->name = $brand->name;
        $this->isModalOpen = true;
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255|unique:brands,name,' . $this->brandId,
        ]);

        Brand::updateOrCreate(
            ['id' => $this->brandId],
            ['name' => $this->name, 'slug' => Str::slug($this->name)]
        );

        $this->resetForm();
        $this->dispatch('alert-success', ['message' => 'Merek berhasil disimpan.']);
    }

    public function delete($id)
    {
        $brand = Brand::findOrFail($id);

        // Cek apakah Brand digunakan di model Item
        if ($brand->items()->exists()) {
            $this->dispatch('alert-error', ['message' => 'Merek tidak dapat dihapus karena masih digunakan di data barang.']);
            return;
        }

        $brand->delete();
        $this->dispatch('alert-success', ['message' => 'Merek berhasil dihapus.']);
    }


    public function resetForm()
    {
        $this->reset(['name', 'brandId', 'isModalOpen']);
    }
}
