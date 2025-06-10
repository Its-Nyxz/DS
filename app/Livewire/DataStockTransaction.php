<?php

namespace App\Livewire;

use App\Models\User;
use Livewire\Component;
use App\Models\Customer;
use App\Models\Supplier;
use Illuminate\Support\Str;
use App\Models\ItemSupplier;
use Livewire\WithPagination;
use App\Models\StockTransaction;
use App\Models\StockTransactionItem;
use App\Notifications\UserNotification;
use Illuminate\Support\Facades\Notification;

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
    public $subtype = '';
    public $customer_name = '';
    public $customer_id;
    public $orderBy = 'transaction_date'; // default field untuk urutan
    public $orderDirection = 'desc'; // default urutan descending

    public function mount($type)
    {
        if (!in_array($type, ['in', 'out', 'retur', 'opname'])) {
            abort(404);
        }

        $this->type = $type;
        $this->resetForm();
    }

    public function getActualType(): string
    {
        if ($this->type === 'retur') {
            // Pastikan subtype hanya antara retur_in atau retur_out
            return in_array($this->subtype, ['retur_in', 'retur_out'])
                ? $this->subtype
                : 'retur_out'; // fallback default
        }

        return $this->type;
    }

    public function render()
    {
        $transactions = StockTransaction::with(['items.item', 'supplier', 'customer'])
            ->when($this->type === 'retur', function ($q) {
                $q->whereIn('type', ['retur_in', 'retur_out']);
            }, function ($q) {
                $q->where('type', $this->type);
            })
            ->whereHas('items.item', fn($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->orderBy($this->orderBy, $this->orderDirection)
            ->paginate(10);

        return view('livewire.data-stock-transaction', [
            'transactions' => $transactions,
            'suppliers' => Supplier::all(),
            'itemSuppliers' => ItemSupplier::with('item', 'item.brand', 'item.unit')
                ->get()
                ->unique('item_id')
                ->values(),

        ]);
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    protected function generateTransactionCode(string $type): string
    {
        $prefix = match ($type) {
            'in' => 'IN',
            'out' => 'OUT',
            'retur' => 'RETUR',
            'opname' => 'SO',
            default => 'TRX',
        };

        $datePart = now()->format('Ymd');
        $randomPart = strtoupper(substr(bin2hex(random_bytes(3)), 0, 5)); // Contoh: AB12C

        return "TRN-{$prefix}-{$datePart}-{$randomPart}";
    }


    public function create()
    {
        $this->resetForm();

        // Gunakan kode acak tapi tetap terstruktur
        do {
            $this->transaction_code = $this->generateTransactionCode($this->type);
        } while (StockTransaction::where('transaction_code', $this->transaction_code)->exists());

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

        $actualType = $this->getActualType();

        if ($actualType === 'retur_in') {
            $rules['customer_name'] = 'required|string|min:3';
        } elseif (in_array($actualType, ['in', 'retur_out'])) {
            $rules['supplier_id'] = 'required|exists:suppliers,id';
        }

        if ($this->type === 'out') {
            // Validasi Customer untuk transaksi keluar
            $rules['customer_name'] = 'required|string|min:3';
        }


        // ✅ Pertama jalankan validasi umum (ini akan munculkan error jika supplier belum dipilih)
        $this->validate($rules);

        // ✅ Setelah lolos validasi, baru lakukan validasi tambahan untuk type "in"
        if ($this->type === 'in') {
            foreach ($this->items as $i => $item) {
                $itemSupplier = ItemSupplier::withTrashed()->find($item['item_supplier_id'] ?? null);

                if (!$itemSupplier) {
                    $this->addError("items.$i.item_supplier_id", 'Barang tidak valid.');
                    continue;
                }

                // Cek apakah item ini tersedia dari supplier yang dipilih
                $itemExistForSupplier = ItemSupplier::where('supplier_id', $this->supplier_id)
                    ->where('item_id', $itemSupplier->item_id)
                    ->whereNull('deleted_at')
                    ->exists();

                if (!$itemExistForSupplier) {
                    $this->addError("items.$i.item_supplier_id", 'Barang ini tidak tersedia dari supplier yang dipilih.');
                }
            }

            if ($this->getErrorBag()->isNotEmpty()) {
                return;
            }
        }

        // Validasi untuk transaksi "out"
        if ($this->type === 'out') {
            foreach ($this->items as $i => $item) {
                $itemSupplier = ItemSupplier::with('item')->find($item['item_supplier_id']);

                if (!$itemSupplier || !$itemSupplier->item) {
                    $this->addError("items.$i.item_supplier_id", 'Barang tidak ditemukan.');
                    continue;
                }

                $itemId = $itemSupplier->item_id;

                // Menghitung stok masuk dan keluar
                $stokMasuk = StockTransactionItem::where('item_id', $itemId)
                    ->whereHas('transaction', function ($q) {
                        $q->where(function ($q2) {
                            $q2->where('type', 'in')->where('is_approved', true)
                                ->orWhere('type', 'retur_in');
                        });
                    })
                    ->sum('quantity');

                $stokKeluar = StockTransactionItem::where('item_id', $itemId)
                    ->whereHas('transaction', function ($q) {
                        $q->whereIn('type', ['out', 'retur_out']);
                    })
                    ->sum('quantity');

                $stokOpname = StockTransactionItem::where('item_id', $itemId)
                    ->whereHas('transaction', fn($q) => $q->where('type', 'adjustment'))
                    ->orderByDesc('transaction_date')
                    ->value('quantity');

                // Mendapatkan konversi unit
                $fromUnitId = $itemSupplier->item->unit_id; // ID unit asal
                $toUnitId = $item['selected_unit_id'] ?? $fromUnitId; // ID unit tujuan (dari form atau unit asal)

                // Dapatkan faktor konversi dengan fallback jika tidak ada konversi
                $conversionFactor = $this->getConversionFactor($fromUnitId, $toUnitId);

                // Jika tidak ada konversi, set konversi ke 1 (tidak ada perubahan unit)
                if ($conversionFactor === null) {
                    $conversionFactor = 1; // Default konversi jika tidak ada konversi yang ditemukan
                }

                // Menghitung stok sekarang dengan faktor konversi
                $stokSekarang = isset($stokOpname)
                    ? $stokOpname * $conversionFactor  // Mengonversi stok opname sesuai unit
                    : ($stokMasuk - $stokKeluar) * $conversionFactor;  // Mengonversi stok masuk dan keluar

                if ($stokSekarang < 0) {
                    $stokSekarang = 0;  // Set stok menjadi 0 jika hasil perhitungan stok negatif
                }

                // Debug untuk melihat nilai stokSekarang
                // dd($stokSekarang, $item['quantity'] > $stokSekarang);

                // Bandingkan dengan quantity yang ingin dikeluarkan
                if ($item['quantity'] > $stokSekarang) {
                    $this->addError("items.$i.quantity", 'Stok tidak mencukupi. Tersedia: ' . $stokSekarang);
                    return; // Hentikan eksekusi jika stok tidak mencukupi
                }
            }
        }

        if ($this->getActualType() === 'retur_in') {
            $slug = Str::slug($this->customer_name);
            $customer = Customer::firstOrCreate(
                ['slug' => $slug],
                ['name' => $this->customer_name]
            );

            $this->customer_id = $customer->id;
        }

        // Jika transaksi keluar, maka customer harus diset
        if ($this->type === 'out') {
            $slug = Str::slug($this->customer_name);
            $customer = Customer::firstOrCreate(
                ['slug' => $slug],
                ['name' => $this->customer_name]
            );

            $this->customer_id = $customer->id;
        }

        $tx = $this->editingId
            ? StockTransaction::find($this->editingId)
            : StockTransaction::create([
                'transaction_code' => $this->transaction_code,
                'type' => $this->getActualType(),
                'created_by' => auth()->id(),
            ]);

        $tx->update([
            'supplier_id' => $this->supplier_id,
            'customer_id' => $this->customer_id ?? null,
            'transaction_date' => $this->transaction_date,
            'description' => $this->description,
        ]);

        $tx->items()->delete();

        foreach ($this->items as $i => $item) {
            $supplier = ItemSupplier::with(['item', 'unitConversions'])->find($item['item_supplier_id']);
            $toUnitId = $item['selected_unit_id'] ?? null;
            $factor = $this->getConversionFactor($supplier->id, $toUnitId);

            $unitPrice = $item['unit_price'] ?? $supplier->harga_beli;
            $convertedQty = $factor > 0 ? $item['quantity'] / $factor : $item['quantity'];
            $convertedPrice = $factor > 0 ? $unitPrice * $factor : $unitPrice;

            $tx->items()->create([
                'item_id' => $supplier->item_id,
                'item_supplier_id' => $supplier->id,
                'unit_id' => $supplier->item->unit_id,
                'quantity' => $convertedQty,
                'unit_price' => $unitPrice,
                'subtotal' => $convertedQty * $convertedPrice,
                'selected_unit_id' => $item['selected_unit_id'] ?? null,
            ]);
        }

        $this->resetForm();
        if ($this->type === 'in') {
            $pemilikUsers = User::role('pemilik')->get();

            $message = 'Transaksi stok masuk <span class="font-bold">'
                . $this->transaction_code . '</span> tanggal <span class="font-bold">'
                . $this->transaction_date . '</span> membutuhkan <span class="text-yellow-500 font-semibold">persetujuan</span>.';

            $url = '/transactions/in'; // ✅ URL diperbaiki
            $title = 'Persetujuan Transaksi Masuk'; // ✅ Tambahkan title

            Notification::send($pemilikUsers, new UserNotification($message, $url, $title));
        }

        $this->dispatch('alert-success', ['message' => 'Transaksi berhasil Disimpan.']);
        $this->isModalOpen = false;
    }

    protected function getConversionFactor($itemSupplierId, $toUnitId): float
    {
        $itemSupplier = ItemSupplier::with('unitConversions')->find($itemSupplierId);

        if (!$itemSupplier || !$toUnitId) {
            return 1; // fallback jika tidak ada konversi
        }

        $conv = $itemSupplier->unitConversions->firstWhere('to_unit_id', $toUnitId);

        return $conv?->factor ?? 1;  // Mengembalikan faktor konversi atau 1 jika tidak ada
    }



    public function delete($id)
    {
        $tx = StockTransaction::findOrFail($id);
        $tx->items()->delete(); // ini akan soft delete jika model pakai SoftDeletes
        $tx->delete(); // ini juga soft delete
    }

    public function restore($id)
    {
        $tx = StockTransaction::withTrashed()->findOrFail($id);
        $tx->restore();
        $tx->items()->withTrashed()->restore();
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
            'id' => $tx->id,
            'type' => $tx->type,
            'code' => $tx->transaction_code,
            'date' => $tx->transaction_date ?? $tx->created_at->toDateString(),
            'supplier' => $tx->supplier->name ?? '-',
            'customer_name' => $tx->type === 'retur_in' || $tx->type === 'out' || $tx->type === 'retur_out'
                ? ($tx->customer->name ?? '-') : null,
            'items' => $tx->items->map(fn($i) => [
                'name' => $i->item->name ?? '-',
                'brand' => $i->item->brand->name ?? '-',
                'qty' => $i->quantity,
                'unit_symbol' => $i->item->unit->symbol ?? '-',
                'selected_unit_id' => $i->selected_unit_id,  // ID unit yang dipilih
                'converted_qty' => $this->getConvertedQuantity($i),  // Quantity yang sudah dikonversi
                'price' => $i->unit_price,
                'subtotal' => $i->subtotal,
            ])->toArray(),
            'total' => $tx->items->sum('subtotal'),
            'note' => $tx->description ?? '',
            'is_approved' => $tx->is_approved,
        ];

        $this->isDetailOpen = true;
    }

    protected function getConvertedQuantity($item)
    {
        $unitId = $item->selected_unit_id ?? $item->item->unit_id;  // Unit yang dipilih atau default unit item
        // Memeriksa apakah unitConversions ada dan tidak null
        if ($item->itemSupplier->unitConversions) {
            $unitConversion = $item->itemSupplier->unitConversions->firstWhere('to_unit_id', $unitId);  // Dapatkan konversi unit

            // Jika ditemukan unit conversion, hitung quantity yang sudah dikonversi
            if ($unitConversion) {
                $factor = $unitConversion->factor;  // Faktor konversi
                return $item->quantity * $factor;  // Menghitung quantity yang dikonversi
            }
        }

        // Jika tidak ada konversi, kembalikan quantity aslinya
        return $item->quantity;
    }


    public function approve($id)
    {
        $tx = StockTransaction::findOrFail($id);

        if ($tx->is_approved) {
            $this->dispatch('alert-error', ['message' => 'Transaksi sudah disetujui.']);
            return;
        }

        $tx->update([
            'is_approved' => true,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        // Notifikasi ke pembuat
        $creator = User::find($tx->created_by);
        if ($creator) {
            $message = 'Transaksi <span class="font-bold">' . $tx->transaction_code . '</span> telah disetujui.';
            $url = '/transactions/' . $tx->type;

            Notification::send($creator, new UserNotification($message, $url, 'Transaksi Disetujui'));
        }

        $this->showDetail($id);
        $this->dispatch('alert-success', ['message' => 'Transaksi berhasil disetujui.']);
    }


    public function reject($id)
    {
        $tx = StockTransaction::findOrFail($id);

        if ($tx->is_approved) {
            $this->dispatch('alert-error', ['message' => 'Transaksi sudah disetujui, tidak bisa ditolak.']);
            return;
        }

        $creator = User::find($tx->created_by);
        $kodeLama = $tx->transaction_code;

        // Jika tipe in, ubah jadi retur_out
        if ($tx->type === 'in') {
            $tx->update([
                'type' => 'retur_out',
                'description' => ($tx->description ? $tx->description . "\n" : '') . ' Transaksi ini ditolak dan dikonversi jadi retur keluar.',
            ]);

            $message = 'Transaksi <span class="font-bold">' . $kodeLama . '</span> ditolak dan dikonversi jadi <span class="text-red-500 font-semibold">Retur Keluar</span>.';
            $url = '/transactions/retur'; // atau sesuaikan dengan path frontend kamu
        } else {
            // Jika bukan tipe in, soft delete biasa
            $tx->delete();

            $message = 'Transaksi <span class="font-bold">' . $kodeLama . '</span> berhasil ditolak dan dihapus.';
            $url = '/transactions/' . $tx->type;
        }

        // Kirim notifikasi
        if ($creator) {
            Notification::send($creator, new UserNotification(
                $message,
                $url,
                'Transaksi Ditolak'
            ));
        }

        $this->isDetailOpen = false;
        $this->dispatch('alert-success', ['message' => 'Transaksi berhasil diproses sebagai penolakan.']);
    }
}
