<?php

namespace App\Livewire;

use Carbon\Carbon;
use App\Models\Item;
use App\Models\Unit;
use App\Models\Brand;
use Livewire\Component;
use App\Models\Supplier;
use App\Models\ItemSupplier;
use App\Models\StockTransaction;
use App\Models\StockTransactionPaymentSchedule;
use Illuminate\Support\Facades\Cache;

class DataDashboard extends Component
{
    public $unitCount, $brandCount, $supplierCount, $itemCount;
    public $itemsPerSupplier = [];
    public $approvedIn = 0;
    public $pendingIn = 0;
    public $totalIn = 0;
    public $chartRange = 'last_7_days';
    public $transactionChartData = [];
    public $totalTransactionCount = 0;
    public $dueDateWarning = [];
    public $totalPendapatan;
    public $totalPengeluaran;
    public $totalKeuntungan;


    public function mount()
    {
        $this->loadMasterCounts();
        $this->loadTransactionCounts();
        $this->loadChartData();
    }

    public function updatedChartRange()
    {
        $this->loadChartData(); // Trigger ulang jika dropdown diganti

        $this->dispatch('chartRangeUpdated', chartData: $this->transactionChartData);
    }

    protected function loadMasterCounts()
    {
        $this->unitCount = Unit::count();
        $this->brandCount = Brand::count();
        $this->supplierCount = Supplier::count();
        $this->itemCount = Item::count();

        $this->itemsPerSupplier = Cache::get('items_per_supplier');

        $this->itemsPerSupplier = ItemSupplier::with('supplier')
            ->whereNull('deleted_at')
            ->get()
            ->groupBy('supplier_id')
            ->map(function ($group) {
                return [
                    'name' => optional($group->first()->supplier)->name ?? 'Unknown',
                    'count' => $group->count(),
                ];
            })
            ->sortByDesc('count')->take(5)
            ->values()
            ->toArray();
    }

    protected function loadTransactionCounts()
    {
        $today = Carbon::today();

        $this->approvedIn = StockTransaction::where('type', 'in')
            ->whereDate('transaction_date', $today)
            ->where('is_approved', true)
            ->count();

        $this->pendingIn = StockTransaction::where('type', 'in')
            ->whereDate('transaction_date', $today)
            ->where('is_approved', false)
            ->count();

        $this->totalIn = $this->approvedIn + $this->pendingIn;
    }

    protected function loadChartData()
    {
        $range = match ($this->chartRange) {
            'today' => [Carbon::today()->startOfDay(), Carbon::today()->endOfDay()],
            'yesterday' => [Carbon::yesterday()->startOfDay(), Carbon::yesterday()->endOfDay()],
            'last_30_days' => [Carbon::now()->subDays(30), Carbon::now()],
            'last_90_days' => [Carbon::now()->subDays(90), Carbon::now()],
            default => [Carbon::now()->subDays(7), Carbon::now()],
        };

        $transactions = StockTransaction::with(['items:id,stock_transaction_id,subtotal']) // hanya kolom dibutuhkan
            ->select('id', 'type', 'transaction_date', 'is_approved') // hindari ambil semua kolom
            ->whereBetween('transaction_date', $range)
            ->whereNull('deleted_at')
            ->when(true, function ($query) {
                $query->where(function ($q) {
                    $q->where('type', '!=', 'in')
                        ->orWhere(function ($q2) {
                            $q2->where('type', 'in')->where('is_approved', true);
                        });
                });
            })
            ->get();

        // Kelompokkan transaksi berdasarkan tipe
        $grouped = $transactions->groupBy('type');

        // Inisialisasi variabel total pendapatan dan total pengeluaran
        $totalPendapatan = 0;
        $totalPengeluaran = 0;

        // Array hasil untuk chart
        $result = [];

        // Proses transaksi berdasarkan tipe
        foreach ($grouped as $type => $items) {
            // Hitung subtotal dari items di setiap transaksi
            $subtotal = $items->flatMap->items->sum('subtotal');
            $label = $this->transactionTypeLabels[$type] ?? ucfirst($type);

            // Tentukan apakah transaksi ini masuk (pendapatan) atau keluar (pengeluaran)
            if (in_array($type, ['in', 'retur_out'])) {
                $totalPengeluaran += $subtotal; // Tambahkan ke total pendapatan
            } elseif (in_array($type, ['out', 'retur_in'])) {
                $totalPendapatan += $subtotal; // Tambahkan ke total pengeluaran
            }

            // Simpan hasil subtotal untuk chart
            $result[$label] = $subtotal;
        }

        // Simpan data chart
        $this->transactionChartData = $result;

        // Hitung total transaksi (pendapatan dan pengeluaran)
        $this->totalTransactionCount = $totalPendapatan + $totalPengeluaran;

        // Simpan total pendapatan dan pengeluaran untuk digunakan di tampilan
        $this->totalPendapatan = $totalPendapatan;
        $this->totalPengeluaran = $totalPengeluaran;
        $this->totalKeuntungan = $totalPendapatan - $totalPengeluaran;
    }


    public array $transactionTypeLabels = [
        'in' => 'Pembelian',
        'out' => 'Penjualan',
        'retur_in' => 'Retur Masuk',
        'retur_out' => 'Retur Keluar',
    ];


    public function render()
    {
        return view('livewire.data-dashboard');
    }
}
