<?php

namespace App\Livewire;

use App\Models\Item;
use App\Models\Unit;
use App\Models\Brand;
use Livewire\Component;
use Illuminate\Support\Str;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

class DataItems extends Component
{
    use WithPagination, WithFileUploads;


    public $name, $unit, $brand, $img, $itemId, $min_stock;
    public $isModalOpen = false;
    public $search = '';
    public $brands, $units;
    public $brand_name = '';
    public $unit_name = '';
    public $orderBy = 'created_at';
    public $orderDirection = 'desc';
    public $suggestions = [
        'brand' => [],
        'unit' => [],
    ];

    public function fetchSuggestions($field, $value)
    {
        $this->suggestions[$field] = [];

        if ($value) {
            $slug = Str::slug($value);

            if ($field === 'brand') {
                $this->suggestions[$field] = Brand::where('slug', 'like', "%$slug%")
                    ->pluck('name')
                    ->toArray();
            } elseif ($field === 'unit') {
                $this->suggestions[$field] = Unit::where('slug', 'like', "%$slug%")
                    ->pluck('name')
                    ->toArray();
            }
        }
    }

    public function selectSuggestion($field, $value)
    {
        if ($field === 'brand') {
            $brand = Brand::where('name', $value)->first();
            $this->brand = $brand?->id;
            $this->brand_name = $value;
        }

        if ($field === 'unit') {
            $unit = Unit::where('name', $value)->first();
            $this->unit = $unit?->id;
            $this->unit_name = $value;
        }

        $this->suggestions[$field] = [];
    }



    public function hideSuggestions($field)
    {
        $this->suggestions[$field] = [];
    }


    public function mount()
    {
        $this->brands = Brand::all();
        $this->units = Unit::all();
    }

    public function render()
    {
        $items = Item::with(['unit', 'brand'])
            ->where(function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('sku', 'like', '%' . $this->search . '%') // Tambahan ini untuk BRG-xxx
                    ->orWhereHas('unit', function ($q) {
                        $q->where('name', 'like', '%' . $this->search . '%')
                            ->orWhere('symbol', 'like', '%' . $this->search . '%');
                    })
                    ->orWhereHas('brand', function ($q) {
                        $q->where('name', 'like', '%' . $this->search . '%');
                    });
            })
            ->orderBy($this->orderBy, $this->orderDirection)
            ->paginate(10);

        return view('livewire.data-items', [
            'items' => $items,
            'units' => Unit::all(),
            'brands' => Brand::all(),
        ]);
    }

    public function openModal()
    {
        $this->resetForm();
        $this->isModalOpen = true;
    }

    public function edit($id)
    {
        $item = Item::with(['unit', 'brand'])->findOrFail($id);

        $this->itemId = $item->id;
        $this->name = $item->name;
        $this->min_stock = $item->min_stock;

        // ID untuk disimpan
        $this->unit = $item->unit_id;
        $this->brand = $item->brand_id;

        // Nama untuk ditampilkan di input form
        $this->unit_name = $item->unit->name ?? '';
        $this->brand_name = $item->brand->name ?? '';

        $this->isModalOpen = true;
    }

    public function save()
    {
        // Cek dan buat merek baru jika perlu
        if (!is_numeric($this->brand) && !empty($this->brand_name)) {
            $slug = Str::slug($this->brand_name);
            $brand = Brand::firstOrCreate(['slug' => $slug], [
                'name' => $this->brand_name,
                'slug' => $slug,
                'symbol' => strtoupper(substr($this->unit_name, 0, 3)) // Atau sesuaikan dengan logika simbol
            ]);
            $this->brand = $brand->id;
        }

        // Cek dan buat satuan baru jika perlu
        if (!is_numeric($this->unit) && !empty($this->unit_name)) {
            $slug = Str::slug($this->unit_name);
            $unit = Unit::firstOrCreate(['slug' => $slug], [
                'name' => $this->unit_name,
                'slug' => $slug,
            ]);
            $this->unit = $unit->id;
        }

        $this->validate([
            'name' => 'required|string|max:255',
            'unit' => 'required|exists:units,id',
            'brand' => 'nullable|exists:brands,id',
            'min_stock' => 'nullable|integer|min:0',
            'img' => 'nullable|image|max:2048', // <= Validasi upload
        ]);

        $exists = Item::where('name', $this->name)
            ->where('brand_id', $this->brand)
            ->when($this->itemId, fn($q) => $q->where('id', '!=', $this->itemId))
            ->exists();

        if ($exists) {
            $this->addError('name', 'Barang dengan kombinasi nama dan merek ini sudah ada.');
            return;
        }

        $item = $this->itemId ? Item::findOrFail($this->itemId) : new Item();

        // Slug & SKU
        $item->sku = $item->exists ? $item->sku : $this->generateSKU($this->name);
        $item->name = $this->name;

        $item->unit_id = $this->unit;
        $item->brand_id = $this->brand;
        $item->min_stock = $this->min_stock;


        // Gambar baru?
        if ($this->img) {
            // Hapus gambar lama jika ada
            if ($item->img && Storage::disk('public')->exists($item->img)) {
                Storage::disk('public')->delete($item->img);
            }

            $item->img = $this->img->store('items', 'public');
        }

        $item->save();

        $this->resetForm();
        $this->dispatch('alert-success', ['message' => 'Barang berhasil disimpan.']);
    }

    public function generateSKU($name)
    {
        $lastId = Item::latest('id')->value('id') ?? 0;
        $prefix = strtoupper(Str::slug(Str::words($name, 1, '')));
        return 'BRG-' . strtoupper(substr($prefix, 0, 3)) . str_pad($lastId + 1, 4, '0', STR_PAD_LEFT);
    }

    public function delete($id)
    {
        $item = Item::findOrFail($id);

        if ($item->img && Storage::disk('public')->exists($item->img)) {
            Storage::disk('public')->delete($item->img);
        }

        $item->delete();
        $this->dispatch('alert-success', ['message' => 'Barang berhasil dihapus.']);
    }

    public function resetForm()
    {
        $this->reset([
            'name',
            'unit',
            'unit_name',
            'brand',
            'brand_name',
            'itemId',
            'min_stock',
            'img',
            'isModalOpen'
        ]);
    }
}
