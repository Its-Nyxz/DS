<?php

namespace App\Livewire;

use Carbon\Carbon;
use App\Models\User;
use Livewire\Component;
use App\Models\Customer;
use App\Models\Supplier;
use Illuminate\Support\Str;
use App\Models\ItemSupplier;
use Livewire\WithPagination;
use App\Models\UnitConversion;
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
    public $is_approved = null;
    public $orderBy = 'transaction_date'; // default field untuk urutan
    public $orderDirection = 'desc'; // default urutan descending
    public $startDate;
    public $endDate;

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
            ->when($this->search, function ($q) {
                $q->whereHas('items.item', fn($q2) => $q2->where('name', 'like', "%{$this->search}%"));
            })
            ->when($this->type === 'in' && filled($this->is_approved), function ($q) {
                $q->where('is_approved', $this->is_approved);
            })
            ->when($this->startDate && $this->endDate, function ($q) {
                $start = Carbon::parse($this->startDate)->startOfDay();
                $end = Carbon::parse($this->endDate)->endOfDay();
                $q->whereBetween('transaction_date', [$start, $end]);
            })
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

    protected function generateTransactionCode(): string
    {
        $actualType = $this->getActualType(); // Ini penting!
        $datePart = now()->format('Ymd');
        $randomPart = strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));

        return match ($actualType) {
            'retur_in' => "RTR-IN-{$datePart}-{$randomPart}",
            'retur_out' => "RTR-OUT-{$datePart}-{$randomPart}",
            'adjustment' => "SO-{$datePart}-{$randomPart}",
            'in' => "TRN-IN-{$datePart}-{$randomPart}",
            'out' => "TRN-OUT-{$datePart}-{$randomPart}",
            default => "TRX-{$datePart}-{$randomPart}",
        };
    }

    protected function generateNewTransactionCode(): void
    {
        do {
            $this->transaction_code = $this->generateTransactionCode();
        } while (StockTransaction::where('transaction_code', $this->transaction_code)->exists());
    }

    public function create(): void
    {
        $this->resetForm();

        if ($this->type === 'retur') {
            // Jika subtype sudah diisi saat create, langsung generate kode
            if (in_array($this->subtype, ['retur_in', 'retur_out'])) {
                $this->generateNewTransactionCode();
            } else {
                $this->transaction_code = ''; // kosongkan dulu
            }
        } else {
            $this->generateNewTransactionCode();
        }

        $this->isModalOpen = true;
    }

    /**
     * Update transaction code when subtype changes (only for 'retur')
     */
    public function updatedSubtype(): void
    {
        if ($this->type === 'retur' && in_array($this->subtype, ['retur_in', 'retur_out'])) {
            $this->generateNewTransactionCode();
        }
    }

    public function edit($id)
    {
        // Mengambil transaksi dengan relasi yang dibutuhkan
        $tx = StockTransaction::with([
            'items.item.unit',
            'items.selectedUnit',
            'items.itemSupplier.unitConversions'
        ])->findOrFail($id);

        // Mengisi properti dengan data transaksi yang ditemukan
        $this->editingId = $tx->id;
        $this->supplier_id = $tx->supplier_id;
        $this->transaction_code = $tx->transaction_code;
        $this->transaction_date = $tx->transaction_date
            ? $tx->transaction_date->format('Y-m-d\TH:i')
            : now()->format('Y-m-d\TH:i');
        $this->description = $tx->description;
        $this->subtype = $tx->type; // Menyimpan tipe transaksi (misal: retur_in, retur_out)

        // Menetapkan customer_name jika tipe transaksi terkait
        if (in_array($tx->type, ['retur_in', 'retur_out', 'out'])) {
            $this->customer_name = $tx->customer?->name ?? '';
        }

        // Menyusun data item untuk form edit
        $this->items = $tx->items->map(function ($i) {
            $baseUnitId = $i->item->unit_id;
            $selectedUnitId = $i->selected_unit_id ?? null;

            // Default faktor konversi (tanpa konversi)
            $factor = 1;
            $convertedQty = $i->quantity;
            $convertedPrice = $i->unit_price;

            // Cek apakah ada konversi unit yang dipilih
            if ($selectedUnitId && $i->itemSupplier) {
                // Cari konversi unit yang sesuai
                $conversion = $i->itemSupplier->unitConversions->firstWhere('to_unit_id', $selectedUnitId);
                if ($conversion && $conversion->factor > 0) {
                    $factor = $conversion->factor;
                    // Menghitung quantity dan harga sesuai faktor konversi
                    $convertedQty = round($i->quantity * $factor, 4);  // Menampilkan 4 digit untuk quantity
                    $convertedPrice = $i->unit_price;
                }
            }

            return [
                'item_supplier_id' => $i->item_supplier_id,
                'item_id' => $i->item_id,
                'quantity' => $convertedQty,  // Quantity setelah konversi
                'unit_price' => $convertedPrice,  // Harga setelah konversi
                'subtotal' => $convertedQty * $convertedPrice,  // Menghitung subtotal
                'selected_unit_id' => $selectedUnitId,  // Unit yang dipilih pada transaksi
                // Menyimpan unit konversi untuk dropdown pada form
                'unit_conversions' => $this->getAllUnitConversionsForItem($i->item_id)
                    ->unique(fn($x) => $x->to_unit_id . '-' . $x->factor)
                    ->values()
                    ->toArray(),
            ];
        })->toArray();

        // Menghitung total berdasarkan item yang telah diset
        $this->calculateTotal();

        // Menampilkan modal untuk edit
        $this->isModalOpen = true;
    }


    protected function getEditableQuantity($item)
    {
        $selectedUnitId = $item->selected_unit_id;
        $factor = 1;

        if ($selectedUnitId && $item->itemSupplier && $item->itemSupplier->unitConversions) {
            $conversion = $item->itemSupplier->unitConversions->firstWhere('to_unit_id', $selectedUnitId);
            $factor = $conversion?->factor ?? 1;
        }

        return round($item->quantity * $factor, 4);
    }

    public function tryAutoFillItemsFromPreviousTransaction(): void
    {
        $actualType = $this->getActualType();

        if ($actualType === 'retur_in' && $this->customer_name && $this->transaction_date) {
            $customerSlug = Str::slug($this->customer_name);
            $customer = Customer::where('slug', $customerSlug)->first();

            if ($customer) {
                $query = StockTransaction::with('items')
                    ->where('type', 'out')
                    ->where('customer_id', $customer->id)
                    ->whereDate('transaction_date', Carbon::parse($this->transaction_date)->toDateString());

                // Optional: cocokkan juga deskripsi jika tersedia
                if (!empty($this->description)) {
                    $query->where('description', $this->description);
                }

                $matchingTx = $query->latest()->first();

                if ($matchingTx) {
                    // Iterasi melalui setiap item dan periksa konversi
                    $this->items = $matchingTx->items->map(fn($i) => [
                        'item_supplier_id' => $i->item_supplier_id,
                        'item_id' => $i->item_id,
                        'quantity' => $i->quantity, // Asli dari transaksi
                        'unit_price' => $i->unit_price,
                        'subtotal' => $i->quantity * $i->unit_price,
                        'selected_unit_id' => $i->selected_unit_id,
                        'unit_conversions' => $this->getAllUnitConversionsForItem($i->item_id)->toArray(),
                    ])->toArray();

                    // Memperhitungkan konversi satuan jika ada
                    foreach ($this->items as &$item) {
                        if ($item['selected_unit_id'] && isset($item['unit_conversions'])) {
                            // Dapatkan faktor konversi dari unit yang dipilih
                            $conversion = collect($item['unit_conversions'])->firstWhere('to_unit_id', $item['selected_unit_id']);
                            if ($conversion && $conversion['factor'] > 0) {
                                $factor = $conversion['factor'];

                                // Update quantity berdasarkan konversi
                                $item['quantity'] = round($item['quantity'] * $factor, 4);  // Sesuaikan dengan jumlah yang sudah dikonversi
                                $item['subtotal'] = $item['quantity'] * $item['unit_price'];  // Update subtotal
                            }
                        }
                    }

                    $this->calculateTotal();

                    $this->dispatch('alert-success', [
                        'message' => 'Data barang berhasil diisi otomatis dari transaksi sebelumnya.'
                    ]);
                } else {
                    $this->dispatch('alert-warning', [
                        'message' => 'Tidak ditemukan transaksi sebelumnya dengan data yang cocok.'
                    ]);
                }
            }
        }

        if ($actualType === 'retur_out' && $this->supplier_id && $this->transaction_date) {
            $query = StockTransaction::with('items')
                ->where('type', 'in')
                ->where('supplier_id', $this->supplier_id)
                ->whereDate('transaction_date', Carbon::parse($this->transaction_date)->toDateString());

            if (!empty($this->description)) {
                $query->where('description', $this->description);
            }

            $matchingTx = $query->latest()->first();

            if ($matchingTx) {
                // Iterasi melalui setiap item dan periksa konversi
                $this->items = $matchingTx->items->map(fn($i) => [
                    'item_supplier_id' => $i->item_supplier_id,
                    'item_id' => $i->item_id,
                    'quantity' => $i->quantity, // Asli dari transaksi
                    'unit_price' => $i->unit_price,
                    'subtotal' => $i->quantity * $i->unit_price,
                    'selected_unit_id' => $i->selected_unit_id,
                    'unit_conversions' => $this->getAllUnitConversionsForItem($i->item_id)->toArray(),
                ])->toArray();

                // Memperhitungkan konversi satuan jika ada
                foreach ($this->items as &$item) {
                    if ($item['selected_unit_id'] && isset($item['unit_conversions'])) {
                        // Dapatkan faktor konversi dari unit yang dipilih
                        $conversion = collect($item['unit_conversions'])->firstWhere('to_unit_id', $item['selected_unit_id']);
                        if ($conversion && $conversion['factor'] > 0) {
                            $factor = $conversion['factor'];

                            // Update quantity berdasarkan konversi
                            $item['quantity'] = round($item['quantity'] * $factor, 4);  // Sesuaikan dengan jumlah yang sudah dikonversi
                            $item['subtotal'] = $item['quantity'] * $item['unit_price'];  // Update subtotal
                        }
                    }
                }

                $this->calculateTotal();

                $this->dispatch('alert-success', [
                    'message' => 'Data barang berhasil diisi otomatis dari transaksi sebelumnya.'
                ]);
            } else {
                $this->dispatch('alert-warning', [
                    'message' => 'Tidak ditemukan transaksi sebelumnya dengan data yang cocok.'
                ]);
            }
        }
    }



    public function updatedCustomerName()
    {
        $this->tryAutoFillItemsFromPreviousTransaction();
    }

    public function updatedSupplierId()
    {
        $this->tryAutoFillItemsFromPreviousTransaction();
    }

    public function updatedTransactionDate()
    {
        $this->tryAutoFillItemsFromPreviousTransaction();
    }


    public function save()
    {
        $actualType = $this->getActualType(); // Ambil tipe sebenarnya lebih awal

        $rules = [
            'transaction_date' => 'required|date_format:Y-m-d\TH:i',
            'items.*.item_supplier_id' => 'required|exists:item_suppliers,id',
            'items.*.quantity' => 'required|numeric|min:1',
        ];

        // Tambahan validasi berdasarkan tipe
        if ($actualType === 'in' || $actualType === 'retur_out') {
            $rules['supplier_id'] = 'required|exists:suppliers,id';
        }

        if ($actualType === 'retur_in' || $this->type === 'out') {
            $rules['customer_name'] = 'required|string|min:3';
        }

        // ✅ Jalankan validasi utama
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

        if ($tx->is_approved) {
            $this->dispatch('alert-error', ['message' => 'Transaksi sudah disetujui dan tidak bisa diedit.']);
            return;
        }

        $tx->update([
            'supplier_id' => $this->supplier_id,
            'customer_id' => $this->customer_id ?? null,
            'transaction_date' => Carbon::parse($this->transaction_date)->format('Y-m-d H:i:s'),
            'description' => $this->description,
        ]);

        $tx->items()->delete();

        foreach ($this->items as $i => $item) {
            $supplier = ItemSupplier::with(['item', 'unitConversions'])->find($item['item_supplier_id']);
            $toUnitId = $item['selected_unit_id'] ?? null;

            $factor = UnitConversion::where('item_supplier_id', $supplier->id)
                ->where('to_unit_id', $toUnitId)
                ->value('factor') ?? 1;

            
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

    protected function getAllUnitConversionsForItem($itemId)
    {
        return UnitConversion::with('toUnit')
            ->whereIn('item_supplier_id', function ($query) use ($itemId) {
                $query->select('id')
                    ->from('item_suppliers')
                    ->where('item_id', $itemId);
            })
            ->get();
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
        $this->customer_name = '';
        $this->customer_id = null;
        $this->subtype = '';
        $this->transaction_date = now()->format('Y-m-d\TH:i');
        $this->description = '';
        $this->items = [
            [
                'item_supplier_id' => null,
                'item_id' => null,
                'quantity' => 1,
                'unit_price' => 0,
                'subtotal' => 0,
                'selected_unit_id' => null,
                'unit_conversions' => [],
            ],
        ];
    }

    public function addItem()
    {
        $this->items[] = [
            'item_supplier_id' => null,
            'item_id' => null,
            'quantity' => 1,
            'unit_price' => 0,
            'subtotal' => 0,
            'selected_unit_id' => null,
            'unit_conversions' => [],
        ];
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

            if ($field === 'selected_unit_id') {
                $this->resetConversion($index);
            }

            // Hitung ulang subtotal
            $qty = (float) ($this->items[$index]['quantity'] ?? 0);
            $price = (float) ($this->items[$index]['unit_price'] ?? 0);
            $this->items[$index]['subtotal'] = $qty * $price;
        }

        $this->calculateTotal();
    }


    protected function resetConversion($index)
    {
        $item = $this->items[$index];

        $supplier = ItemSupplier::with(['item', 'unitConversions'])->find($item['item_supplier_id'] ?? null);

        if (!$supplier) return;

        $basePrice = (float) $supplier->harga_beli;
        $qty = (float) ($item['quantity'] ?? 1);
        $selectedUnitId = $item['selected_unit_id'] ?? null;

        // Cek konversi
        $factor = UnitConversion::where('item_supplier_id', $supplier->id)
            ->where('to_unit_id', $selectedUnitId)
            ->value('factor') ?? 1;

        // Normalisasi: quantity = quantity / factor, price = price * factor
        if ($factor > 0 && $factor !== 1) {
            $this->items[$index]['quantity'] = $qty / $factor;
            $this->items[$index]['unit_price'] = $basePrice * $factor;
        } else {
            // Tidak ada konversi atau satuan asli
            $this->items[$index]['quantity'] = $qty;
            $this->items[$index]['unit_price'] = $basePrice;
        }

        // Update subtotal
        $this->items[$index]['subtotal'] = $this->items[$index]['quantity'] * $this->items[$index]['unit_price'];
    }


    public function setItemSupplier($index, $value)
    {
        $supplier = ItemSupplier::with('item')->find((int) $value);

        if ($supplier) {
            $this->items[$index]['item_supplier_id'] = $supplier->id;
            $this->items[$index]['item_id'] = $supplier->item_id; // simpan item_id untuk ambil semua konversi
            $this->items[$index]['unit_price'] = (float) $supplier->harga_beli;
            $this->items[$index]['quantity'] ??= 1;
            $this->items[$index]['subtotal'] = $supplier->harga_beli * $this->items[$index]['quantity'];
            $this->items[$index]['selected_unit_id'] = null; // ✅ reset dulu, jangan biarkan default tetap 1
            $allConversions = $this->getAllUnitConversionsForItem($supplier->item_id);

            $uniqueConversions = $allConversions->unique(function ($item) {
                return $item->to_unit_id . '-' . $item->factor;
            })->values();

            $this->items[$index]['unit_conversions'] = $uniqueConversions->toArray();


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
        $tx = StockTransaction::with(['supplier', 'items.item.unit', 'items.selectedUnit'])->findOrFail($id);

        $this->detail = [
            'id' => $tx->id,
            'type' => $tx->type,
            'code' => $tx->transaction_code,
            'date' => $tx->transaction_date
                ? $tx->transaction_date->format('d/m/Y H:i')
                : $tx->created_at->format('d/m/Y H:i'),
            'supplier' => $tx->supplier->name ?? '-',
            'customer_name' => $tx->type === 'retur_in' || $tx->type === 'out' || $tx->type === 'retur_out'
                ? ($tx->customer->name ?? '-') : null,
            'items' => $tx->items->map(fn($i) => [
                'name' => $i->item->name ?? '-',
                'brand' => $i->item->brand->name ?? '-',
                'qty' => $i->quantity,
                'unit_symbol' => $i->selectedUnit->symbol ?? $i->item->unit->symbol ?? '-',
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

    public function exportDetailPdf($id)
    {
        $tx = StockTransaction::with(['supplier', 'customer', 'items.item.unit', 'items.selectedUnit'])->findOrFail($id);

        foreach ($tx->items as $item) {
            $qty = $item->quantity;
            $unitSymbol = $item->selectedUnit->symbol ?? $item->item->unit->symbol ?? '-';

            // Jika selected_unit_id digunakan, cari konversinya
            if ($item->selected_unit_id && $item->itemSupplier) {
                $conversion = $item->itemSupplier->unitConversions
                    ->firstWhere('to_unit_id', $item->selected_unit_id);

                if ($conversion && $conversion->factor > 0) {
                    $qty = $item->quantity * $conversion->factor;
                }
            }

            // Simpan di temporary property
            $item->converted_qty = $qty;
            $item->converted_unit_symbol = $unitSymbol;
        }

        $html = view('pdf.transaction-detail', compact('tx'))->render(); // Simpan HTML ke view

        $pdf = new \TCPDF();
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 11);
        $pdf->writeHTML($html, true, false, true, false, '');

        return response()->stream(
            fn() => $pdf->Output("transaksi-{$tx->transaction_code}.pdf", 'I'),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => "inline; filename=transaksi-{$tx->transaction_code}.pdf",
            ]
        );
    }

    public function exportPdfByType()
    {
        $type = $this->type ?? 'in'; // Sesuaikan dengan tipe yang sedang aktif
        $transactions = StockTransaction::with(['supplier', 'customer', 'items.item.unit', 'items.selectedUnit'])
            ->where('type', $type)
            ->orderByDesc('transaction_date')
            ->get();

        $typeLabels = [
            'in' => 'Transaksi Masuk',
            'out' => 'Transaksi Keluar',
            'retur_in' => 'Retur dari Customer',
            'retur_out' => 'Retur ke Supplier',
            'opname' => 'Stock Opname',
        ];

        $html = view('pdf.transaction_report_by_type', [
            'transactions' => $transactions,
            'type_label' => $typeLabels[$type] ?? strtoupper($type),
        ])->render();

        $pdf = new \TCPDF();
        $pdf->SetMargins(10, 10, 10);
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 10);
        $pdf->writeHTML($html, true, false, true, false, '');

        $labels = [
            'in' => 'Masuk',
            'out' => 'Keluar',
            'retur' => 'Retur',
            'opname' => 'Stock Opname',
        ];

        $labelType = $labels[$this->type] ?? ucfirst($this->type);

        return response()->stream(function () use ($pdf) {
            $pdf->Output('laporan_transaksi.pdf', 'I');
        }, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="laporan_transaksi_' . strtolower($labelType) . '.pdf"',
        ]);
    }
}
