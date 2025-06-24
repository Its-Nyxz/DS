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
use App\Models\StockTransactionItem;
use Illuminate\Support\Facades\Auth;
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
    public $isPemilik = false;
    public $salesTransactionCount = 0;
    public $totalBarangTerjual = 0;


    public function mount()
    {
        $this->isPemilik = Auth::user()?->hasRole('pemilik');
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

        if ($this->isPemilik) {
            $this->approvedIn = StockTransaction::where('type', 'in')
                ->whereDate('transaction_date', $today)
                ->where('is_approved', true)
                ->count();

            $this->pendingIn = StockTransaction::where('type', 'in')
                ->whereDate('transaction_date', $today)
                ->where('is_approved', false)
                ->count();

            $this->totalIn = $this->approvedIn + $this->pendingIn;
        } else {
            // Jumlah transaksi penjualan
            $this->salesTransactionCount = CashTransaction::whereDate('transaction_date', $today)
                ->where(function ($q) {
                    $q->whereIn('transaction_type', ['income', 'transfer_in', 'refund_in'])
                        ->orWhere(function ($q2) {
                            $q2->where('transaction_type', 'payment')->where('debt_credit', 'piutang');
                        })
                        ->orWhere(function ($q3) {
                            $q3->where('transaction_type', 'stock')->where('debt_credit', 'piutang');
                        });
                })
                ->count();

            // Jumlah barang yang dijual (dari StockTransaction type 'out')
            $stockOutIds = StockTransaction::where('type', 'out')
                ->whereDate('transaction_date', $today)
                ->whereNull('deleted_at')
                ->pluck('id');

            $this->totalBarangTerjual = StockTransactionItem::whereIn('stock_transaction_id', $stockOutIds)
                ->sum('quantity');
        }
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

        $totalPendapatan = 0;
        $totalPengeluaran = 0;

        foreach ($transactions as $tx) {
            if ($this->isDebit($tx)) {
                $totalPendapatan += $tx->amount;
            } elseif ($this->isCredit($tx)) {
                $totalPengeluaran += $tx->amount;
            }
        }

        $this->totalPendapatan = $totalPendapatan;
        $this->totalPengeluaran = $totalPengeluaran;
        $this->totalKeuntungan = $totalPendapatan - $totalPengeluaran;
        $this->totalTransactionCount = $totalPendapatan + $totalPengeluaran;

        $this->transactionChartData = $this->isPemilik
            ? ['Pemasukan' => $totalPendapatan, 'Pengeluaran' => $totalPengeluaran]
            : ['Pendapatan' => $totalPendapatan];
    }

    /**
     * Determine if transaction is income
     */
    protected function isDebit($tx): bool
    {
        return in_array($tx->transaction_type, ['income', 'transfer_in', 'refund_in']) ||
            ($tx->transaction_type === 'stock' && $tx->debt_credit === 'piutang') ||
            ($tx->transaction_type === 'payment' && $tx->debt_credit === 'piutang');
    }

    /**
     * Determine if transaction is expense
     */
    protected function isCredit($tx): bool
    {
        return in_array($tx->transaction_type, ['expense']) ||
            ($tx->transaction_type === 'stock' && $tx->debt_credit === 'utang') ||
            ($tx->transaction_type === 'payment' && $tx->debt_credit === 'utang');
    }



    public function render()
    {
        return view('livewire.data-dashboard');
    }
}
