<?php

namespace App\Livewire;

use Carbon\Carbon;
use App\Models\Item;
use App\Models\Unit;
use App\Models\Brand;
use Livewire\Component;
use App\Models\Supplier;
use App\Models\ItemSupplier;
use App\Models\CashTransaction;
use App\Models\StockTransaction;
use Illuminate\Support\Facades\Cache;
use App\Models\StockTransactionPaymentSchedule;

class DataDashboard extends Component
{
    public $unitCount, $brandCount, $supplierCount, $itemCount;
    public $itemsPerSupplier = [];
    public $approvedIn = 0;
    public $pendingIn = 0;
    public $totalIn = 0;
    public $chartRange = 'today';
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

        $transactions = CashTransaction::whereBetween('transaction_date', $range)
            ->where('amount', '!=', 0)
            ->get();

        // Debit: pemasukan ke kas
        $getDebit = function ($tx) {
            return (
                in_array($tx->transaction_type, ['income', 'transfer_in', 'refund_in']) ||
                ($tx->transaction_type === 'stock' && $tx->debt_credit === 'piutang') ||
                ($tx->transaction_type === 'payment' && $tx->debt_credit === 'piutang')
            ) ? $tx->amount : 0;
        };

        // Kredit: pengeluaran dari kas
        $getKredit = function ($tx) {
            return (
                in_array($tx->transaction_type, ['expense']) ||
                ($tx->transaction_type === 'stock' && $tx->debt_credit === 'utang') ||
                ($tx->transaction_type === 'payment' && $tx->debt_credit === 'utang')
            ) ? $tx->amount : 0;
        };

        $totalPendapatan = 0;
        $totalPengeluaran = 0;

        foreach ($transactions as $tx) {
            $totalPendapatan += $getDebit($tx);
            $totalPengeluaran += $getKredit($tx);
        }

        $this->transactionChartData = [
            'Pemasukan' => $totalPendapatan,
            'Pengeluaran' => $totalPengeluaran,
        ];

        $this->totalPendapatan = $totalPendapatan;
        $this->totalPengeluaran = $totalPengeluaran;
        $this->totalKeuntungan = $totalPendapatan - $totalPengeluaran;
        $this->totalTransactionCount = $totalPendapatan + $totalPengeluaran;
    }


    public array $transactionTypeLabels = [
        'income' => 'Pemasukan',
        'expense' => 'Pengeluaran',
        'payment' => 'Pembayaran',
        'stock' => 'Transaksi Stok',
        'transfer_in' => 'Transfer Masuk',
        'adjustment_in' => 'Penyesuaian',
        // 'refund_in' => 'Pengembalian',
    ];


    public function render()
    {
        return view('livewire.data-dashboard');
    }
}
