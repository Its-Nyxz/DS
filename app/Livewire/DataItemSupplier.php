<?php

namespace App\Livewire;

use App\Models\Item;
use App\Models\Unit;
use Livewire\Component;
use App\Models\Supplier;
use App\Models\ItemSupplier;
use Livewire\WithPagination;

class DataItemSupplier extends Component
{
    use WithPagination;

    public $search = '';
    public $supplierId;
    public $supplierName;
    public $itemInputs = []; // Array of ['item_id' => ..., 'harga_beli' => ..., 'is_default' => true/false]
    public $isModalOpen = false;
    public $existingItemIds = [];
    protected $rules = [
        'supplierId' => 'required|exists:suppliers,id',
        'itemInputs.*.item_id' => 'required|exists:items,id',
        'itemInputs.*.harga_beli' => 'nullable|numeric|min:0',
        'itemInputs.*.is_default' => 'boolean',
        'itemInputs.*.conversion.from_unit_id' => 'nullable|numeric|exists:units,id',
        'itemInputs.*.conversion.to_unit_id' => 'nullable|numeric|exists:units,id',
        'itemInputs.*.conversion.factor' => 'nullable|numeric|min:0.01',
    ];

    public $supplier_name = '';
    public $suggestions = [
        'supplier' => [],
        'item' => []
    ];

    public function fetchSuggestions($field, $value, $index = null)
    {
        $query = null;
        if ($field === 'supplier') {
            $query = Supplier::where('name', 'like', "%$value%")->pluck('name');
            $this->suggestions['supplier'] = $query->toArray();
        }

        if ($field === 'item') {
            $query = Item::where('name', 'like', "%$value%")->pluck('name');
            $this->suggestions['item'][$index] = $query->toArray();
        }
    }

    public function hideSuggestions($field, $index = null)
    {
        if ($index !== null) {
            $this->suggestions[$field][$index] = [];
        } else {
            $this->suggestions[$field] = [];
        }
    }

    public function selectSuggestion($field, $indexOrValue, $value = null)
    {
        if ($field === 'supplier') {
            $supplier = Supplier::where('name', $indexOrValue)->first();
            $this->supplierId = $supplier?->id;
            $this->supplier_name = $supplier?->name ?? '';
            $this->suggestions['supplier'] = [];
        }

        if ($field === 'item') {
            $item = Item::where('name', $value)->first();
            if ($item) {
                $this->itemInputs[$indexOrValue]['item_id'] = $item->id;
                $this->itemInputs[$indexOrValue]['item_name'] = $item->name;
            }
            $this->suggestions['item'][$indexOrValue] = [];
        }
    }


    public function render()
    {
        $suppliers = Supplier::with(['items' => function ($query) {
            $query->withPivot([
                'harga_beli',
                'is_default',
                'min_qty',
                'lead_time_days',
                'catatan',
                'deleted_at'
            ])->wherePivotNull('deleted_at');
        }])->where('name', 'like', "%{$this->search}%")->paginate(10);
        $allItems = Item::orderBy('name')->get();
        $allSuppliers = Supplier::orderBy('name')->get();
        $units = Unit::orderBy('name')->get();

        return view('livewire.data-item-supplier', compact('suppliers', 'allItems', 'allSuppliers', 'units'));
    }

    public function openModal()
    {
        $this->resetForm();
        $this->isModalOpen = true;
    }

    public function edit($supplierId)
    {
        $supplier = Supplier::findOrFail($supplierId);
        $this->supplierId = $supplier->id;
        $this->supplier_name = $supplier->name;

        $this->itemInputs = [];
        $this->existingItemIds = [];

        foreach ($supplier->items()->wherePivotNull('deleted_at')->get() as $item) {
            $this->existingItemIds[] = $item->id;
            $itemSupplier = ItemSupplier::with('unitConversions')
                ->where('supplier_id', $supplier->id)
                ->where('item_id', $item->id)
                ->first();

            $conversions = $itemSupplier->unitConversions->map(fn($conv) => [
                'to_unit_id' => $conv->to_unit_id,
                'factor' => $conv->factor,
            ])->toArray();

            $this->itemInputs[] = [
                'item_id' => $item->id,
                'item_name' => $item->name,
                'harga_beli' => $item->pivot->harga_beli,
                'is_default' => (bool) $item->pivot->is_default,
                'min_qty' => $item->pivot->min_qty,
                'lead_time_days' => $item->pivot->lead_time_days,
                'catatan' => $item->pivot->catatan,
                'conversions' => $conversions,
            ];
        }

        $this->isModalOpen = true;
    }

    public function addItemInput()
    {
        $this->itemInputs[] = [
            'item_id' => '',
            'item_name' => '',
            'harga_beli' => null,
            'is_default' => false,
            'min_qty' => null,
            'lead_time_days' => null,
            'catatan' => '',
            'conversion' => [
                'from_unit_id' => null,
                'to_unit_id' => null,
                'factor' => null,
            ],
        ];
    }

    public function updatedItemInputs()
    {
        $itemIds = array_column($this->itemInputs, 'item_id');
        if (count($itemIds) !== count(array_unique($itemIds))) {
            $this->dispatch('alert-error', ['message' => 'Barang tidak boleh duplikat.']);
        }
    }

    public function removeItemInput($index)
    {
        unset($this->itemInputs[$index]);
        $this->itemInputs = array_values($this->itemInputs); // Reindex
    }

    public function save()
    {
        $this->validate();

        $submittedItemIds = collect($this->itemInputs)->pluck('item_id')->filter()->unique()->values();

        foreach ($this->itemInputs as $input) {
            $itemSupplier = ItemSupplier::withTrashed()->updateOrCreate(
                [
                    'supplier_id' => $this->supplierId,
                    'item_id' => $input['item_id'],
                ],
                [
                    'harga_beli' => $input['harga_beli'],
                    'is_default' => $input['is_default'],
                    'min_qty' => $input['min_qty'],
                    'lead_time_days' => $input['lead_time_days'],
                    'catatan' => $input['catatan'],
                    'deleted_at' => null, // restore kalau soft deleted
                ]
            );

            $itemSupplier->refresh();

            if (isset($input['conversions']) && is_array($input['conversions'])) {
                $item = Item::find($input['item_id']);
                $fromUnitId = $item?->unit_id;

                // Reset konversi sebelumnya
                $itemSupplier->unitConversions()->delete();

                foreach ($input['conversions'] as $conv) {
                    if (isset($conv['to_unit_id'], $conv['factor'])) {
                        $itemSupplier->unitConversions()->create([
                            'from_unit_id' => $fromUnitId,
                            'to_unit_id' => $conv['to_unit_id'],
                            'factor' => $conv['factor'],
                            'item_supplier_id' => $itemSupplier->id,
                        ]);
                    }
                }
            }

            $deletedItemIds = collect($this->existingItemIds)->diff($submittedItemIds);

            foreach ($deletedItemIds as $deletedItemId) {
                ItemSupplier::where('supplier_id', $this->supplierId)
                    ->where('item_id', $deletedItemId)
                    ->update(['deleted_at' => now()]);
            }
        }

        $this->dispatch('alert-success', ['message' => 'Relasi supplier dan barang berhasil disimpan.']);
        $this->resetForm();
    }

    public function deleteAll($supplierId, $itemId)
    {
        ItemSupplier::where([
            'supplier_id' => $supplierId,
            'item_id' => $itemId,
        ])->update(['deleted_at' => now()]);

        $this->dispatch('alert-success', ['message' => 'Relasi berhasil dihapus.']);
    }

    public function restore($supplierId, $itemId)
    {
        ItemSupplier::onlyTrashed()
            ->where('supplier_id', $supplierId)
            ->where('item_id', $itemId)
            ->restore();

        $this->dispatch('alert-success', ['message' => 'Relasi berhasil dipulihkan.']);
    }

    public function addConversion($index)
    {
        $this->itemInputs[$index]['conversions'][] = ['to_unit_id' => null, 'factor' => null];
    }

    public function removeConversion($index, $subIndex)
    {
        unset($this->itemInputs[$index]['conversions'][$subIndex]);
        $this->itemInputs[$index]['conversions'] = array_values($this->itemInputs[$index]['conversions']);
    }

    public function resetForm()
    {
        $this->reset(['supplierId', 'supplierName', 'itemInputs', 'isModalOpen']);
    }
}
