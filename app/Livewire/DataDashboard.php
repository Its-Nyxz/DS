<?php

namespace App\Livewire;

use App\Models\Item;
use App\Models\Unit;
use App\Models\Brand;
use Livewire\Component;
use App\Models\Supplier;
use App\Models\ItemSupplier;

class DataDashboard extends Component
{
    public $unitCount, $brandCount, $supplierCount, $itemCount;
    public $itemsPerSupplier = [];

    public function mount()
    {
        $this->unitCount = Unit::count();
        $this->brandCount = Brand::count();
        $this->supplierCount = Supplier::count();
        $this->itemCount = Item::count();

        $this->itemsPerSupplier = ItemSupplier::with('supplier')
            ->whereNull('deleted_at')
            ->get()
            ->groupBy('supplier_id') // ✅ Group berdasarkan ID
            ->map(function ($group) {
                return [
                    'name' => optional($group->first()->supplier)->name ?? 'Unknown',
                    'count' => $group->count(),
                ];
            })
            ->values()
            ->toArray();
        // dd($this->itemsPerSupplier);
    }

    public function render()
    {
        return view('livewire.data-dashboard');
    }
}
