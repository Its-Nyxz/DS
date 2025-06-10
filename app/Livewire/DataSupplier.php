<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Supplier;
use Illuminate\Support\Str;
use Livewire\WithPagination;

class DataSupplier extends Component
{
    use WithPagination;

    public $supplierId, $name, $phone, $address;
    public $isModalOpen = false;
    public $search = '';
    public $orderBy = 'created_at';
    public $orderDirection = 'desc';
    protected $listeners = ['refreshDataSupplier' => 'render'];

    public function render()
    {
        $suppliers = Supplier::where('name', 'like', "%{$this->search}%")
            ->orderBy($this->orderBy, $this->orderDirection)
            ->paginate(10);

        return view('livewire.data-supplier', compact('suppliers'));
    }

    public function openModal()
    {
        $this->resetForm();
        $this->isModalOpen = true;
    }

    public function edit($id)
    {
        $supplier = Supplier::findOrFail($id);
        $this->supplierId = $supplier->id;
        $this->name = $supplier->name;
        $this->phone = $supplier->phone;
        $this->address = $supplier->address;
        $this->isModalOpen = true;
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
        ]);

        Supplier::updateOrCreate(
            ['id' => $this->supplierId],
            [
                'name' => $this->name,
                'slug' => Str::slug($this->name),
                'phone' => $this->phone,
                'address' => $this->address,
            ]
        );

        $this->resetForm();
        $this->dispatch('alert-success', ['message' => 'Data supplier berhasil disimpan.']);
    }

    public function delete($id)
    {
        $supplier = Supplier::findOrFail($id);

        // Cek relasi dengan Item
        if ($supplier->items()->exists()) {
            $this->dispatch('alert-error', ['message' => 'Supplier tidak dapat dihapus karena masih digunakan di data barang.']);
            return;
        }

        $supplier->delete();
        $this->dispatch('alert-success', ['message' => 'Data supplier berhasil dihapus.']);
    }

    public function resetForm()
    {
        $this->reset(['supplierId', 'name', 'phone', 'address', 'isModalOpen']);
    }
}
