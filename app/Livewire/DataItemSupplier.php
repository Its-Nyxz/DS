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

        // ✅ Validasi konversi ganda:
        'itemInputs.*.conversions.*.to_unit_id' => 'nullable|exists:units,id|required_with:itemInputs.*.conversions.*.factor',
        'itemInputs.*.conversions.*.factor' => 'nullable|numeric|min:0.0001|required_with:itemInputs.*.conversions.*.to_unit_id',
    ];

    public $supplier_name = '';
    public $suggestions = [
        'supplier' => [],
        'item' => []
    ];

    public $orderBy = 'created_at';
    public $orderDirection = 'desc';

    public function fetchSuggestions($field, $value, $index = null)
    {
        $query = null;
        if ($field === 'supplier') {
            $query = Supplier::where('name', 'like', "%$value%")->pluck('name');
            $this->suggestions['supplier'] = $query->toArray();
        }

        if ($field === 'item') {
            $query = Item::with('brand')
                ->where(function ($q) use ($value) {
                    $q->where('name', 'like', "%$value%")
                        ->orWhereHas('brand', fn($q) => $q->where('name', 'like', "%$value%"));
                })
                ->limit(10)
                ->get()
                ->map(fn($item) => [
                    'id' => $item->id,
                    'label' => $item->name . ' ' . ($item->brand->name ?? '-'),
                ]);

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
            $item = Item::find($value); // $value adalah ID
            if ($item) {
                $this->itemInputs[$indexOrValue]['item_id'] = $item->id;
                $this->itemInputs[$indexOrValue]['item_name'] = $item->name . ' ' . ($item->brand->name ?? '-');
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
        }])->where('name', 'like', "%{$this->search}%")
            ->orderBy($this->orderBy, $this->orderDirection)
            ->paginate(10);
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
                'item_name' => $item->name . ' ' . ($item->brand->name ?? '-'),
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
            'conversions' => [
                ['to_unit_id' => null, 'factor' => null]
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

    protected function syncItemSupplier(array $input): ?ItemSupplier
    {
        if (empty($input['item_id']) || !is_numeric($input['item_id'])) {
            return null;
        }

        return ItemSupplier::withTrashed()->updateOrCreate(
            [
                'supplier_id' => $this->supplierId,
                'item_id' => $input['item_id'],
            ],
            [
                'harga_beli' => $input['harga_beli'] ?? 0,
                'is_default' => $input['is_default'] ?? false,
                'min_qty' => $input['min_qty'] ?? 0,
                'lead_time_days' => $input['lead_time_days'] ?? 0,
                'catatan' => $input['catatan'] ?? '',
                'deleted_at' => null,
            ]
        );
    }

    protected function syncConversions(ItemSupplier $itemSupplier, array $input): void
    {
        $item = Item::find($input['item_id'] ?? null);
        if (!$item || !$item->unit_id) {
            logger()->warning('Item atau unit_id tidak valid.', ['input' => $input]);
            return;
        }

        $fromUnitId = $item->unit_id;

        // Pastikan item_supplier_id diatur dengan benar
        if (!$itemSupplier->id) {
            // Jika itemSupplier tidak ada, buat atau update
            $itemSupplier = $this->syncItemSupplier($input);  // Buat atau update itemSupplier jika belum ada
            if (!$itemSupplier) {
                logger()->error('Item Supplier ID tidak ditemukan setelah pembuatan atau pembaruan.');
                return;
            }
        }

        $conversions = $input['conversions'] ?? [];

        foreach ($conversions as $conv) {
            $toUnitId = $conv['to_unit_id'] ?? null;
            $factor = $conv['factor'] ?? null;

            if ($toUnitId && is_numeric($factor) && $factor > 0) {
                try {
                    // Periksa apakah konversi sudah ada
                    $existingConversion = $itemSupplier->unitConversions()
                        ->where('from_unit_id', $fromUnitId)
                        ->where('to_unit_id', $toUnitId)
                        ->first();

                    if ($existingConversion) {
                        // Jika konversi sudah ada, lakukan update semua kolom yang diperlukan
                        $existingConversion->update([
                            'to_unit_id' => $toUnitId,  // Update to_unit_id jika ada perubahan
                            'factor' => $factor,         // Update factor jika ada perubahan
                        ]);
                    } else {
                        // Jika konversi belum ada, buat baru
                        $itemSupplier->unitConversions()->create([
                            'from_unit_id' => $fromUnitId,
                            'to_unit_id' => $toUnitId,
                            'factor' => $factor,
                            'item_supplier_id' => $itemSupplier->id, // Pastikan item_supplier_id terisi
                        ]);
                    }
                } catch (\Throwable $e) {
                    logger()->error('Gagal simpan konversi.', [
                        'item_supplier_id' => $itemSupplier->id,
                        'conv' => $conv,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    public function save()
    {
        foreach ($this->itemInputs as &$item) {
            if (!isset($item['conversions']) || !is_array($item['conversions'])) {
                $item['conversions'] = [['to_unit_id' => null, 'factor' => null]];
            }
        }

        $this->validate();

        $submittedItemIds = collect($this->itemInputs)->pluck('item_id')->filter()->unique()->values();

        foreach ($this->itemInputs as $input) {
            $itemSupplier = $this->syncItemSupplier($input);

            if ($itemSupplier) {
                $this->syncConversions($itemSupplier, $input);
            } else {
                logger()->warning('Gagal membuat itemSupplier untuk:', $input);
            }
        }

        $this->softDeleteMissingItems($submittedItemIds);

        $this->resetForm();

        $this->dispatch('alert-success', ['message' => 'Relasi supplier dan barang berhasil disimpan.']);
    }

    protected function softDeleteMissingItems($submittedItemIds): void
    {
        $deletedItemIds = collect($this->existingItemIds)->diff($submittedItemIds);

        foreach ($deletedItemIds as $deletedItemId) {
            ItemSupplier::where('supplier_id', $this->supplierId)
                ->where('item_id', $deletedItemId)
                ->update(['deleted_at' => now()]);
        }
    }

    public function deleteAll($supplierId)
    {
        $itemSuppliers = ItemSupplier::where('supplier_id', $supplierId)->get();

        foreach ($itemSuppliers as $itemSupplier) {
            $itemSupplier->unitConversions()->delete(); // hapus konversi
            $itemSupplier->update(['deleted_at' => now()]); // soft delete
        }

        $this->dispatch('alert-success', ['message' => 'Semua relasi dan konversi berhasil dihapus.']);
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
        $this->reset(['supplierId', 'supplierName', 'supplier_name', 'itemInputs', 'isModalOpen']);
    }
}
