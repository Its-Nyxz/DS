<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Supplier;
use App\Models\ItemSupplier;
use Livewire\WithPagination;
use App\Models\StockTransaction;
use App\Models\StockTransactionItem;

class DataStockTransaction extends Component
{
    use WithPagination;

    public string $type;
    public string $search = '';

    // Modal state
    public bool $isModalOpen = false;
    public ?int $editingId = null;
    // Form fields
    public $supplier_id, $transaction_date, $description = '';
    public array $items = [];
    public float $total = 0;
    public string $transaction_code = '';
    public bool $isDetailOpen = false;
    public array $detail = [];


    public function mount($type)
    {
        if (!in_array($type, ['in', 'out', 'retur', 'opname'])) {
            abort(404);
        }

        $this->type = $type;
        $this->resetForm();
    }

    public function render()
    {
        $transactions = StockTransaction::with(['items.item', 'supplier'])
            ->where('type', $this->type)
            ->whereHas('items.item', fn($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('livewire.data-stock-transaction', [
            'transactions' => $transactions,
            'suppliers' => Supplier::all(),
            'itemSuppliers' => ItemSupplier::with('item')->get(),
        ]);
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function create()
    {
        $this->resetForm();

        $prefix = match ($this->type) {
            'in' => 'IN',
            'out' => 'OUT',
            'retur' => 'RETUR',
            'opname' => 'SO',
            default => 'TRX',
        };

        $last = StockTransaction::where('type', $this->type)->count() + 1;
        $this->transaction_code = 'TRN-' . $prefix . '-' . str_pad($last, 5, '0', STR_PAD_LEFT);

        $this->isModalOpen = true;
    }

    public function edit($id)
    {
        $tx = StockTransaction::with('items')->findOrFail($id);

        $this->editingId = $tx->id;
        $this->supplier_id = $tx->supplier_id;
        $this->transaction_code = $tx->transaction_code;
        $this->transaction_date = $tx->transaction_date;
        $this->description = $tx->description;

        $this->items = $tx->items->map(fn($i) => [
            'item_supplier_id' => $i->item_supplier_id,
            'quantity' => $i->quantity,
            'unit_price' => $i->unit_price,
            'subtotal' => $i->subtotal,
        ])->toArray();

        $this->isModalOpen = true;
    }

    public function save()
    {
        $rules = [
            'transaction_date' => 'required|date',
            'items.*.item_supplier_id' => 'required|exists:item_suppliers,id',
            'items.*.quantity' => 'required|numeric|min:1',
        ];

        if (in_array($this->type, ['in', 'retur'])) {
            $rules['supplier_id'] = 'required|exists:suppliers,id';
        }

        // ✅ Pertama jalankan validasi umum (ini akan munculkan error jika supplier belum dipilih)
        $this->validate($rules);

        // ✅ Setelah lolos validasi, baru lakukan validasi tambahan untuk type "in"
        if ($this->type === 'in') {
            foreach ($this->items as $i => $item) {
                $itemSupplier = ItemSupplier::find($item['item_supplier_id'] ?? null);

                if (!$itemSupplier || $itemSupplier->supplier_id != $this->supplier_id) {
                    $this->addError("items.$i.item_supplier_id", 'Barang ini tidak tersedia untuk supplier yang dipilih.');
                }
            }

            // Jika ditemukan error, hentikan proses penyimpanan
            if ($this->getErrorBag()->isNotEmpty()) {
                return;
            }
        }

        if ($this->type === 'out') {
            foreach ($this->items as $i => $item) {
                $itemSupplier = ItemSupplier::with('item')->find($item['item_supplier_id']);

                if (!$itemSupplier || !$itemSupplier->item) {
                    $this->addError("items.$i.item_supplier_id", 'Barang tidak ditemukan.');
                    continue;
                }

                $itemId = $itemSupplier->item_id;

                // Menambah stok
                $stokMasuk = StockTransactionItem::where('item_id', $itemId)
                    ->whereHas('transaction', function ($q) {
                        $q->where(function ($q2) {
                            $q2->where('type', 'in')->where('is_approve', true)
                                ->orWhere('type', 'retur_in');
                        });
                    })
                    ->sum('quantity');

                // Mengurangi stok
                $stokKeluar = StockTransactionItem::where('item_id', $itemId)
                    ->whereHas('transaction', function ($q) {
                        $q->whereIn('type', ['out', 'retur_out']);
                    })
                    ->sum('quantity');

                // Penyesuaian stok dari opname (override jumlah stok, jadi ambil opname terakhir)
                $stokOpname = StockTransactionItem::where('item_id', $itemId)
                    ->whereHas('transaction', fn($q) => $q->where('type', 'opname'))
                    ->orderByDesc('transaction_date')
                    ->value('quantity');

                // Hitung stok sekarang
                $stokSekarang = isset($stokOpname)
                    ? $stokOpname
                    : ($stokMasuk - $stokKeluar);


                // Bandingkan dengan quantity yang ingin dikeluarkan
                if ($item['quantity'] > $stokSekarang) {
                    $this->addError("items.$i.quantity", 'Stok tidak mencukupi. Tersedia: ' . $stokSekarang);
                }
            }

            // Jika ada error, hentikan proses simpan
            if ($this->getErrorBag()->isNotEmpty()) {
                return;
            }
        }

        $tx = $this->editingId
            ? StockTransaction::find($this->editingId)
            : StockTransaction::create([
                'transaction_code' => $this->transaction_code,
                'type' => $this->type,
                'created_by' => auth()->id(),
            ]);

        $tx->update([
            'supplier_id' => $this->supplier_id,
            'transaction_date' => $this->transaction_date,
            'description' => $this->description,
        ]);

        $tx->items()->delete();

        foreach ($this->items as $i) {
            $supplier = ItemSupplier::with('item')->find($i['item_supplier_id']);
            $tx->items()->create([
                'item_id' => $supplier->item_id,
                'item_supplier_id' => $supplier->id,
                'unit_id' => $supplier->item->unit_id,
                'quantity' => $i['quantity'],
                'unit_price' => $i['unit_price'],
                'subtotal' => $i['quantity'] * $i['unit_price'],
            ]);
        }

        $this->resetForm();
        $this->dispatch('alert-success', ['message' => 'Transaksi berhasil Disimpan.']);
        $this->isModalOpen = false;
    }

    public function delete($id)
    {
        $tx = StockTransaction::findOrFail($id);
        $tx->items()->delete();
        $tx->delete();
    }

    public function resetForm()
    {
        $this->editingId = null;
        $this->supplier_id = null;
        $this->transaction_date = now()->toDateString();
        $this->description = '';
        $this->items = [
            ['item_supplier_id' => null, 'quantity' => 1, 'unit_price' => 0, 'subtotal' => 0],
        ];
    }

    public function addItem()
    {
        $this->items[] = ['item_supplier_id' => null, 'quantity' => 1, 'unit_price' => 0, 'subtotal' => 0];
    }

    public function removeItem($index)
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function updatedItems($value, $key)
    {
        $parts = explode('.', $key);

        if (count($parts) === 3) {
            [$parent, $index, $field] = $parts;

            // Selalu hitung ulang subtotal
            $qty = (float) ($this->items[$index]['quantity'] ?? 0);
            $price = (float) ($this->items[$index]['unit_price'] ?? 0);
            $this->items[$index]['subtotal'] = $qty * $price;
        }

        // Update total transaksi
        $this->calculateTotal();
    }

    public function setItemSupplier($index, $value)
    {
        $supplier = ItemSupplier::find((int) $value);

        if ($supplier) {
            $this->items[$index]['item_supplier_id'] = $supplier->id;
            $this->items[$index]['unit_price'] = (float) $supplier->harga_beli;
            $this->items[$index]['quantity'] ??= 1;
            $this->items[$index]['subtotal'] = $supplier->harga_beli * $this->items[$index]['quantity'];

            $this->calculateTotal();
        }
    }


    public function calculateTotal()
    {
        $this->total = collect($this->items)->sum(function ($i) {
            return (float) ($i['quantity'] ?? 0) * (float) ($i['unit_price'] ?? 0);
        });
    }

    public function showDetail($id)
    {
        $tx = StockTransaction::with(['supplier', 'items.item'])->findOrFail($id);

        $this->detail = [
            'code' => $tx->transaction_code,
            'date' => $tx->transaction_date ?? $tx->created_at->toDateString(),
            'supplier' => $tx->supplier->name ?? '-',
            'items' => $tx->items->map(fn($i) => [
                'name' => $i->item->name ?? '-',
                'qty' => $i->quantity,
                'price' => $i->unit_price,
                'subtotal' => $i->subtotal,
            ])->toArray(),
            'total' => $tx->items->sum('subtotal'),
            'note' => $tx->description ?? '',
        ];

        $this->isDetailOpen = true;
    }
}
