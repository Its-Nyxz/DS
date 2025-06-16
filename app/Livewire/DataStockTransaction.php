<?php

namespace App\Livewire;

use Carbon\Carbon;
use App\Models\Item;
use App\Models\User;
use Livewire\Component;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\StockOpname;
use Illuminate\Support\Str;
use App\Models\ItemSupplier;
use Livewire\WithPagination;
use App\Models\UnitConversion;
use App\Models\StockTransaction;
use Illuminate\Support\Facades\Log;
use App\Models\StockTransactionItem;
use App\Models\StockTransactionPayment;
use App\Notifications\UserNotification;
use Illuminate\Support\Facades\Notification;
use App\Models\StockTransactionPaymentSchedule;

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
    public string $difference_reason = '';
    public string $opname_type = '';
    public $payment_schedules = []; // array untuk cicilan bertahap (opsional)
    public string $payment_type = 'cash'; // default 'cash' atau 'term'
    public bool $isPaymentModalOpen = false;
    public $payment_transaction_id;
    public float $payment_amount = 0;
    public $payment_paid_at;
    public string $payment_note = '';
    public $schedules = []; // List termin
    public $selected_schedule_id = null; // Termin yang dipilih saat bayar
    public $payment_method;
    public $paymentDetails = [];
    public $isPaymentDetailModalOpen = false;

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
                $actualType = $this->type === 'opname' ? 'adjustment' : $this->type;
                $q->where('type', $actualType);
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
        $randomPart = now()->format('His');

        return match ($actualType) {
            'retur_in' => "RTR-IN-{$datePart}-{$randomPart}",
            'retur_out' => "RTR-OUT-{$datePart}-{$randomPart}",
            'opname' => "SO-{$datePart}-{$randomPart}",
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

        if ($this->type === 'in') {
            $this->initDefaultPaymentSchedule();
        }

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

    private function initDefaultPaymentSchedule(): void
    {
        $this->payment_schedules = [
            ['amount' => 0, 'due_date' => null],
        ];
    }

    public function addPaymentSchedule(): void
    {
        $this->payment_schedules[] = ['amount' => 0, 'due_date' => null];
    }

    public function removePaymentSchedule($index): void
    {
        unset($this->payment_schedules[$index]);
        $this->payment_schedules = array_values($this->payment_schedules);
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

        $this->items = $tx->items->map(function ($i) {
            $baseUnitId = $i->item->unit_id;
            $selectedUnitId = $i->selected_unit_id ?? null;

            // Default faktor konversi (tanpa konversi)
            $factor = 1;
            $convertedQty = $i->quantity;
            $convertedPrice = $i->unit_price;

            // Cek apakah ada konversi unit yang dipilih
            if ($selectedUnitId && $i->itemSupplier) {
                $conversion = $i->itemSupplier->unitConversions->firstWhere('to_unit_id', $selectedUnitId);
                if ($conversion && $conversion->factor > 0) {
                    $factor = $conversion->factor;
                    $convertedQty = round($i->quantity * $factor, 4);  // Menampilkan 4 digit untuk quantity
                    $convertedPrice = $i->unit_price;
                }
            }

            // Mengambil System Stock
            $systemStock = $this->getSystemStockForItem($i->item_id);

            // Menghitung get_stock
            $getStock = $systemStock - $convertedQty; // System Stock dikurangi dengan quantity yang diinput

            // Menyimpan hasil dalam array items
            return [
                'item_supplier_id' => $i->item_supplier_id,
                'item_id' => $i->item_id,
                'quantity' => $convertedQty,
                'unit_price' => $convertedPrice,
                'subtotal' => $convertedQty * $convertedPrice,
                'selected_unit_id' => $selectedUnitId,
                'unit_conversions' => $this->getAllUnitConversionsForItem($i->item_id)
                    ->unique(fn($x) => $x->to_unit_id . '-' . $x->factor)
                    ->values()
                    ->toArray(),
                'system_stock' => $systemStock, // Menambahkan system stock
                'get_stock' => round($getStock, 2), // Menyimpan get_stock dalam array items
            ];
        })->toArray();

        if ($tx->type === 'in') {
            $this->payment_type = $tx->paymentSchedules()->exists() ? 'term' : 'cash';

            if ($this->payment_type === 'term') {
                $this->payment_schedules = $tx->paymentSchedules->map(function ($s) {
                    return [
                        'amount' => $s->scheduled_amount,
                        'due_date' => Carbon::parse($s->due_date)->format('Y-m-d'),
                    ];
                })->toArray();
            } else {
                $this->payment_schedules = [];
            }
        }

        // Menghitung total berdasarkan item yang telah diset
        $this->calculateTotal();

        // Menampilkan modal untuk edit
        $this->isModalOpen = true;
    }

    protected function getSystemStockForItem($itemId)
    {
        $stokMasuk = StockTransactionItem::where('item_id', $itemId)
            ->whereHas('transaction', fn($q) => $q->whereIn('type', ['in', 'retur_in']))
            ->sum('quantity');

        $stokKeluar = StockTransactionItem::where('item_id', $itemId)
            ->whereHas('transaction', fn($q) => $q->whereIn('type', ['out', 'retur_out']))
            ->sum('quantity');

        // Penyesuaian dari stock_opname (adjustment)
        $adjustment = StockOpname::where('item_id', $itemId)->sum('difference');

        return $stokMasuk - $stokKeluar + $adjustment;
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

    public function updatedPaymentType($value)
    {
        if ($value === 'cash') {
            $this->payment_schedules = [];
        } elseif ($value === 'term' && empty($this->payment_schedules)) {
            $this->payment_schedules[] = ['amount' => 0, 'due_date' => null];
        }
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

        if (empty($this->items)) {
            $this->addError('items', 'Minimal satu item harus ditambahkan.');
            return;
        }

        if ($this->total <= 0) {
            $this->addError('total', 'Total transaksi tidak boleh nol.');
            return;
        }
        // ✅ Jalankan validasi utama
        $this->validate($rules);

        // Jika tipe transaksi adalah 'opname', simpan ke StockOpname, bukan ke StockTransaction
        if ($this->type === 'opname') {
            if (empty($this->difference_reason)) {
                $this->addError('difference_reason', 'Alasan perbedaan harus diisi.');
                return;
            }

            if (empty($this->opname_type)) {
                $this->addError('opname_type', 'Jenis opname harus dipilih.');
                return;
            }
            $this->saveStockOpname(); // Fungsi baru untuk menyimpan stock opname
        } else {
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

                if ($this->payment_type === 'term') {

                    foreach ($this->payment_schedules as $i => $schedule) {
                        if ($schedule['due_date'] < $this->transaction_date) {
                            $this->addError("payment_schedules.$i.due_date", 'Tanggal jatuh tempo tidak boleh sebelum tanggal transaksi.');
                        }

                        if (empty($schedule['amount']) || empty($schedule['due_date'])) {
                            $this->addError('payment_schedules.' . $i, 'Semua termin harus memiliki jumlah dan jatuh tempo.');
                        }
                    }

                    $sum = collect($this->payment_schedules)->sum(function ($ps) {
                        return is_numeric($ps['amount']) ? floatval($ps['amount']) : 0;
                    });

                    if ($sum != $this->total) {
                        $this->addError('payment_schedules', 'Total termin harus sama dengan total transaksi.');
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


            if ($this->type === 'in') {
                if ($this->payment_type === 'term') {
                    $this->storePaymentSchedules($tx);

                    $tx->update([
                        'total_amount' => array_sum(array_column($this->payment_schedules, 'amount')),
                        'is_fully_paid' => false,
                        'fully_paid_at' => null,
                    ]);
                } else {
                    // Pembayaran cash langsung lunas
                    $tx->update([
                        'total_amount' => $this->total,
                        'is_fully_paid' => true,
                        'fully_paid_at' => now(),
                    ]);
                }
            }

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
    }

    protected function saveStockOpname()
    {
        // Buat atau update transaksi stok
        $tx = $this->editingId
            ? StockTransaction::findOrFail($this->editingId)
            : new StockTransaction();

        $tx->fill([
            'transaction_code' => $this->transaction_code,
            'type' => 'adjustment', // untuk opname
            'created_by' => auth()->id(),
            'transaction_date' => Carbon::parse($this->transaction_date),
            'description' => $this->description,
            'difference_reason' => $this->difference_reason ?? null,
            'opname_type' => $this->opname_type ?? null,
        ])->save();

        // Hapus item transaksi dan opname lama (jika edit)
        $tx->items()->delete();
        $tx->stockOpnames()->delete(); // relasi perlu didefinisikan di model StockTransaction

        foreach ($this->items as $item) {
            $itemId = $item['item_id'];
            $actualStock = floatval($item['quantity']);
            $systemStock = floatval($item['system_stock']);
            $difference = $actualStock - $systemStock;
            $status = $this->determineOpnameStatus($actualStock, $systemStock);

            $itemSupplier = ItemSupplier::with('item')->find($item['item_supplier_id']);
            $unitId = $itemSupplier?->item?->unit_id;

            // Simpan ke transaksi item
            $tx->items()->create([
                'item_id' => $itemId,
                'item_supplier_id' => $item['item_supplier_id'],
                'unit_id' => $unitId,
                'quantity' => $actualStock,
                'unit_price' => 0,
                'subtotal' => 0,
                'selected_unit_id' => $item['selected_unit_id'] ?? null,
            ]);

            // Simpan ke tabel stock_opnames
            StockOpname::create([
                'stock_transaction_id' => $tx->id,
                'item_id' => $itemId,
                'actual_stock' => $actualStock,
                'system_stock' => $systemStock,
                'difference' => $difference,
                'status' => $status,
                'created_by' => auth()->id(),
            ]);
        }

        $this->dispatch('alert-success', ['message' => 'Stock Opname berhasil disimpan.']);
        $this->isModalOpen = false;
    }

    private function storePaymentSchedules(StockTransaction $tx): void
    {
        foreach ($this->payment_schedules as $schedule) {
            $tx->paymentSchedules()->create([
                'scheduled_amount' => $schedule['amount'],
                'due_date' => $schedule['due_date'],
            ]);
        }
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
        $this->difference_reason = '';
        $this->opname_type = '';
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
                'status' => null,
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
        // Cek apakah item_supplier_id valid
        $itemSupplier = ItemSupplier::with('item', 'unitConversions')->find((int) $value);

        if ($itemSupplier) {
            // Pastikan selected_unit_id adalah integer dan reset selected_unit_id saat barang berubah
            $this->items[$index]['selected_unit_id'] = null; // Reset selected_unit_id

            // Simpan informasi dasar item
            $this->items[$index]['item_supplier_id'] = $itemSupplier->id;
            $this->items[$index]['item_id'] = $itemSupplier->item_id;
            $this->items[$index]['unit_price'] = (float) $itemSupplier->harga_beli;
            if (!isset($this->items[$index]['quantity'])) {
                $this->setQuantity($index, 1);
            }
            $this->items[$index]['subtotal'] = $itemSupplier->harga_beli * $this->items[$index]['quantity'];

            // Ambil semua konversi unit untuk item yang dipilih
            $allConversions = $this->getAllUnitConversionsForItem($itemSupplier->item_id);

            // Dapatkan konversi yang unik berdasarkan 'to_unit_id' dan faktor
            $uniqueConversions = $allConversions->unique(function ($item) {
                return $item->to_unit_id . '-' . $item->factor;
            });

            // Simpan konversi-konversi unik tersebut
            $this->items[$index]['unit_conversions'] = $uniqueConversions->values()->toArray();

            // Ambil system stock untuk item yang dipilih (tanpa konversi untuk saat ini)
            $systemStock = $this->getSystemStockForItem($itemSupplier->item_id);

            // Reset system_stock ke nilai normal
            $this->items[$index]['system_stock'] = round($systemStock, 2);

            // Hitung get_stock setelah update item supplier
            $getStock = $systemStock - $this->items[$index]['quantity']; // Dapatkan nilai get_stock
            $this->items[$index]['get_stock'] = round($getStock, 2);

            // Ambil simbol unit untuk unit default
            $unitSymbol = $itemSupplier->item->unit->symbol;

            // Simpan simbol unit di item
            $this->items[$index]['unit_symbol'] = $unitSymbol;

            // Hitung ulang total setelah update
            $this->calculateTotal();
        } else {
            // Jika barang tidak dipilih, reset system_stock, unit_symbol dan selected_unit_id
            $this->items[$index]['system_stock'] = 0;  // Reset ke 0 atau nilai default lainnya
            $this->items[$index]['unit_symbol'] = '-';  // Atur simbol unit menjadi default atau kosong
            $this->items[$index]['selected_unit_id'] = null; // Reset selected_unit_id
            $this->items[$index]['get_stock'] = 0;  // Reset ke 0 atau nilai default lainnya
        }
    }

    public function updatedItems($value, $key)
    {
        $parts = explode('.', $key);

        if (count($parts) !== 3) return;

        [$parent, $index, $field] = $parts;

        $itemSupplierId = $this->items[$index]['item_supplier_id'] ?? null;
        if (!$itemSupplierId) return;

        $itemId = $this->items[$index]['item_id'] ?? null;
        $quantity = floatval($this->items[$index]['quantity'] ?? 0);
        $selectedUnitId = $this->items[$index]['selected_unit_id'] ?? null;

        // Tangani perubahan quantity
        if ($field === 'quantity') {
            $this->items[$index]['quantity'] = $quantity;

            $systemStock = $this->getSystemStockForItem($itemId);

            if ($selectedUnitId) {
                $factor = $this->getConversionFactor($itemSupplierId, $selectedUnitId);
                $baseQty = $quantity / $factor;

                $getStock = $systemStock - $baseQty;

                $this->items[$index]['get_stock'] = round($getStock * $factor, 2);
                $this->items[$index]['system_stock'] = round($systemStock * $factor, 2);
                $actual = $this->items[$index]['quantity'] ?? 0;
                $system = $this->items[$index]['system_stock'] ?? 0;

                $this->items[$index]['status'] = $this->determineOpnameStatus($actual, $system);
            } else {
                $getStock = $systemStock - $quantity;

                $this->items[$index]['get_stock'] = round($getStock, 2);
                $this->items[$index]['system_stock'] = round($systemStock, 2);
                $actual = $this->items[$index]['quantity'] ?? 0;
                $system = $this->items[$index]['system_stock'] ?? 0;

                $this->items[$index]['status'] = $this->determineOpnameStatus($actual, $system);
            }
        }

        // Tangani perubahan selected_unit_id
        if ($field === 'selected_unit_id') {
            $selectedUnitId = (int) $selectedUnitId;

            $itemSupplier = ItemSupplier::with(['item', 'unitConversions'])->find($itemSupplierId);
            if (!$itemSupplier) return;

            $systemStock = $this->getSystemStockForItem($itemSupplier->item_id);

            if (!$selectedUnitId) {
                // Reset ke satuan dasar
                $this->items[$index]['system_stock'] = round($systemStock, 2);
                $this->items[$index]['get_stock'] = round($systemStock - $quantity, 2);
                $actual = $this->items[$index]['quantity'] ?? 0;
                $system = $this->items[$index]['system_stock'] ?? 0;

                $this->items[$index]['status'] = $this->determineOpnameStatus($actual, $system);
                $this->items[$index]['unit_symbol'] = $itemSupplier->item->unit->symbol ?? '-';
            } else {
                // Gunakan satuan konversi
                $factor = $this->getConversionFactor($itemSupplier->id, $selectedUnitId);
                $convertedStock = $systemStock * $factor;
                $baseQty = $quantity / $factor;

                $this->items[$index]['system_stock'] = round($convertedStock, 2);
                $this->items[$index]['get_stock'] = round(($systemStock - $baseQty) * $factor, 2);
                $actual = $this->items[$index]['quantity'] ?? 0;
                $system = $this->items[$index]['system_stock'] ?? 0;

                $this->items[$index]['status'] = $this->determineOpnameStatus($actual, $system);

                $unitConversion = $itemSupplier->unitConversions->firstWhere('to_unit_id', $selectedUnitId);
                $this->items[$index]['unit_symbol'] = $unitConversion?->toUnit->symbol ?? '-';
            }
        }

        // Hitung ulang subtotal dan total
        $qty = (float) ($this->items[$index]['quantity'] ?? 0);
        $price = (float) ($this->items[$index]['unit_price'] ?? 0);
        $this->items[$index]['subtotal'] = $qty * $price;

        $this->calculateTotal();
    }

    public function updated($name, $value)
    {
        $this->updatedItems($value, $name);
    }

    public function setQuantity($index, $value)
    {
        $value = max(0, floatval($value)); // Pastikan tidak negatif
        $this->items[$index]['quantity'] = $value;

        $this->updatedItems($value, "items.$index.quantity");
    }

    public function calculateTotal()
    {
        $this->total = collect($this->items)->sum(function ($i) {
            return (float) ($i['quantity'] ?? 0) * (float) ($i['unit_price'] ?? 0);
        });
    }

    public function showDetail($id)
    {
        $tx = StockTransaction::with(['supplier', 'items.item.unit', 'items.selectedUnit', 'stockOpnames'])->findOrFail($id);
        // Mapping stock opname hanya jika type 'opname'
        $stockOpnameMap = $tx->type === 'adjustment'
            ? $tx->stockOpnames->keyBy('item_id')
            : collect();
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
            'opname_type' => $tx->opname_type ?? null,
            'difference_reason' => $tx->difference_reason ?? null,
            'items' => $tx->items->map(function ($i) use ($tx, $stockOpnameMap) {
                $convertedQty = $this->getConvertedQuantity($i);

                $itemData = [
                    'name' => $i->item->name ?? '-',
                    'brand' => $i->item->brand->name ?? '-',
                    'qty' => $i->quantity,
                    'unit_symbol' => $i->selectedUnit->symbol ?? $i->item->unit->symbol ?? '-',
                    'selected_unit_id' => $i->selected_unit_id,
                    'converted_qty' => $convertedQty,
                    'price' => $i->unit_price,
                    'subtotal' => $i->subtotal,
                ];

                // Hanya untuk transaksi opname
                if ($tx->type === 'adjustment') {
                    $opname = $stockOpnameMap[$i->item_id] ?? null;
                    $itemData['status'] = $opname?->status ?? '-';
                    $itemData['system_stock'] = $opname?->system_stock ?? '-';
                    $itemData['difference'] = $opname?->difference ?? 0;
                }

                return $itemData;
            })->toArray(),
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
                // Menghitung quantity yang dikonversi dan membulatkan ke atas (ceil)
                return ceil($item->quantity * $factor);  // Menggunakan ceil untuk pembulatan ke atas
            }
        }

        // Jika tidak ada konversi, kembalikan quantity aslinya
        return ceil($item->quantity); // Membulatkan ke atas jika tidak ada konversi
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


    public function reject($id, $reason)
    {
        $tx = StockTransaction::findOrFail($id);

        if ($tx->is_approved) {
            $this->dispatch('alert-error', ['message' => 'Transaksi sudah disetujui, tidak bisa ditolak.']);
            return;
        }

        $creator = User::find($tx->created_by);
        $kodeLama = $tx->transaction_code;

        if ($tx->type === 'in') {
            $tx->update([
                'type' => 'retur_out',
                'description' => ($tx->description ? $tx->description . "\n" : '') .
                    "Transaksi ini ditolak dan diubah jadi retur keluar.\nAlasan: $reason",
            ]);

            $message = 'Transaksi <span class="font-bold">' . $kodeLama . '</span> ditolak dan diubah jadi <span class="text-red-500 font-semibold">Retur Keluar</span>.';
            $url = '/transactions/retur';
        } else {
            $tx->description = ($tx->description ? $tx->description . "\n" : '') . "Transaksi ditolak. Alasan: $reason";
            $tx->save();
            $tx->delete();

            $message = 'Transaksi <span class="font-bold">' . $kodeLama . '</span> berhasil ditolak dan dihapus.';
            $url = '/transactions/' . $tx->type;
        }

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

    protected function determineOpnameStatus(float $actual, float $system): string
    {
        return match (true) {
            $actual > $system => 'tambah',
            $actual < $system => 'penyusutan',
            default => 'sesuai',
        };
    }

    public function openPaymentModal($transactionId)
    {
        $this->payment_transaction_id = $transactionId;

        $tx = StockTransaction::with('paymentSchedules')->findOrFail($transactionId);
        $this->schedules = $tx->paymentSchedules->map(function ($s) {
            return [
                'id' => $s->id,
                'due_date' => Carbon::parse($s->due_date)->format('d/m/Y'),
                'amount' => $s->scheduled_amount,
                'is_paid' => $s->is_paid,
            ];
        })->toArray();

        $this->selected_schedule_id = null;
        $this->payment_paid_at = now()->format('Y-m-d');
        $this->payment_amount = 0;
        $this->payment_note = '';
        $this->isPaymentModalOpen = true;
    }

    public function updatedSelectedScheduleId($value)
    {
        if ($value) {
            $schedule = StockTransactionPaymentSchedule::find($value);
            if ($schedule && !$schedule->is_paid) {
                $this->payment_amount = $schedule->scheduled_amount;
            }
        } else {
            $this->payment_amount = null;
        }
    }


    public function savePayment()
    {
        $this->validate([
            'payment_amount' => 'required|numeric|min:1',
            'payment_paid_at' => 'required|date',
            'payment_method' => 'required|string',
        ]);

        $tx = StockTransaction::findOrFail($this->payment_transaction_id);

        $payment = $tx->payments()->create([
            'payment_schedule_id' => $this->selected_schedule_id,
            'amount' => $this->payment_amount,
            'payment_date' => $this->payment_paid_at,
            'payment_method' => $this->payment_method,
            'reference_number' => $this->reference_number ?? 'TRMN-' . now()->format('Ymd-His'),
            'note' => $this->payment_note,
            'paid_by' => auth()->id(),
        ]);

        // Jika user pilih termin tertentu, tandai sudah dibayar
        if ($this->selected_schedule_id) {
            $schedule = StockTransactionPaymentSchedule::find($this->selected_schedule_id);
            $schedule->update([
                'is_paid' => true,
                'paid_at' => $this->payment_paid_at,
            ]);
        }

        // Update status lunas transaksi
        $totalPaid = $tx->payments()->sum('amount');
        $tx->update([
            'is_fully_paid' => $totalPaid >= $tx->items->sum('subtotal'),
            'fully_paid_at' => $totalPaid >= $tx->items->sum('subtotal') ? now() : null,
        ]);

        $this->isPaymentModalOpen = false;
        $this->dispatch('alert-success', ['message' => 'Pembayaran berhasil disimpan.']);
    }

    public function openPaymentDetailModal($transactionId)
    {
        $transaction = StockTransaction::with('payments.payer')->findOrFail($transactionId);

        $this->paymentDetails = $transaction->payments->map(function ($p) {
            return [
                'date' => $p->payment_date->format('d/m/Y'),
                'amount' => $p->amount,
                'method' => $p->payment_method,
                'ref' => $p->reference_number,
                'note' => $p->note,
                'by' => $p->paidBy->name ?? '-',
            ];
        })->toArray();

        $this->isPaymentDetailModalOpen = true;
    }

    public function exportDetailPdf($id)
    {
        $tx = StockTransaction::with([
            'supplier',
            'customer',
            'items.item.unit',
            'items.selectedUnit',
            'stockOpnames'
        ])->findOrFail($id);

        // Mapping stockOpname by item_id
        $stockOpnameMap = $tx->stockOpnames->keyBy('item_id');

        foreach ($tx->items as $item) {
            $qty = $item->quantity;
            $unitSymbol = $item->selectedUnit->symbol ?? $item->item->unit->symbol ?? '-';

            // Konversi jika selected_unit digunakan
            if ($item->selected_unit_id && $item->itemSupplier) {
                $conversion = $item->itemSupplier->unitConversions
                    ->firstWhere('to_unit_id', $item->selected_unit_id);

                if ($conversion && $conversion->factor > 0) {
                    $qty = $item->quantity * $conversion->factor;
                }
            }

            // Simpan data konversi
            $item->converted_qty = $qty;
            $item->converted_unit_symbol = $unitSymbol;

            // Jika type adjustment, tambahkan data opname
            if ($tx->type === 'adjustment') {
                $opname = $stockOpnameMap[$item->item_id] ?? null;

                $item->difference = $opname?->difference ?? 0;
                $item->system_stock = $opname?->system_stock ?? 0;
                $item->status = $opname?->status ?? '-';
            }
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
        $type = $this->type ?? 'in'; // Tipe aktif
        $mappedType = $type === 'opname' ? 'adjustment' : $type;

        $transactions = StockTransaction::with([
            'supplier',
            'customer',
            'items.item.unit',
            'items.selectedUnit',
            'items.item.brand',
            'items.itemSupplier.unitConversions.toUnit',
            'stockOpnames'
        ])
            ->when($type === 'retur', function ($q) {
                $q->whereIn('type', ['retur_in', 'retur_out']);
            }, function ($q) use ($mappedType) {
                $q->where('type', $mappedType);
            })
            ->when($this->search, function ($q) {
                $q->whereHas('items.item', fn($q2) => $q2->where('name', 'like', '%' . $this->search . '%'));
            })
            ->when($type === 'in' && filled($this->is_approved), function ($q) {
                $q->where('is_approved', $this->is_approved);
            })
            ->when($this->startDate && $this->endDate, function ($q) {
                $start = Carbon::parse($this->startDate)->startOfDay();
                $end = Carbon::parse($this->endDate)->endOfDay();
                $q->whereBetween('transaction_date', [$start, $end]);
            })
            ->orderBy($this->orderBy ?? 'transaction_date', $this->orderDirection ?? 'desc')
            ->get();

        // Konversi & tambahan data untuk setiap transaksi
        foreach ($transactions as $tx) {
            $stockOpnameMap = $tx->stockOpnames->keyBy('item_id');

            foreach ($tx->items as $item) {
                $qty = $item->quantity;
                $unitSymbol = $item->selectedUnit->symbol ?? $item->item->unit->symbol ?? '-';

                if ($item->selected_unit_id && $item->itemSupplier) {
                    $conversion = $item->itemSupplier->unitConversions
                        ->firstWhere('to_unit_id', $item->selected_unit_id);

                    if ($conversion && $conversion->factor > 0) {
                        $qty = $item->quantity * $conversion->factor;
                        $unitSymbol = $conversion->toUnit->symbol ?? $unitSymbol;
                    }
                }

                $item->converted_qty = $qty;
                $item->converted_unit_symbol = $unitSymbol;

                if ($type === 'opname') {
                    $opname = $stockOpnameMap[$item->item_id] ?? null;
                    $item->difference = $opname?->difference ?? 0;
                    $item->system_stock = $opname?->system_stock ?? 0;
                    $item->status = $opname?->status ?? '-';
                }
            }
        }

        $typeLabels = [
            'in' => 'Transaksi Masuk',
            'out' => 'Transaksi Keluar',
            'retur_in' => 'Retur dari Customer',
            'retur_out' => 'Retur ke Supplier',
            'adjustment' => 'Stock Opname',
        ];

        $html = view('pdf.transaction_report_by_type', [
            'transactions' => $transactions,
            'type_label' => $typeLabels[$mappedType] ?? strtoupper($mappedType),
        ])->render();

        $pdf = new \TCPDF();
        $pdf->SetMargins(10, 10, 10);
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 10);
        $pdf->writeHTML($html, true, false, true, false, '');

        $labelType = $typeLabels[$mappedType] ?? ucfirst($mappedType);
        Carbon::setLocale('id'); // Set lokal ke Bahasa Indonesia

        $startFormatted = Carbon::parse($this->startDate)->translatedFormat('d F Y');
        $endFormatted = Carbon::parse($this->endDate)->translatedFormat('d F Y');
        $filename = 'laporan_transaksi_' . strtolower($labelType) . "_{$startFormatted}_{$endFormatted}.pdf";

        return response()->stream(function () use ($pdf, $labelType) {
            $pdf->Output('laporan_transaksi_' . strtolower($labelType) . '.pdf', 'I');
        }, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }
}
