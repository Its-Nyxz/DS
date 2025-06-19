<?php

namespace App\Livewire;

use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\CashTransaction;
use App\Models\StockTransaction;

class DataCashTransaction extends Component
{
    use WithPagination;

    public $type;
    public $search = '';
    public $orderBy = 'transaction_date';
    public $orderDirection = 'desc';
    public $startDate;
    public $endDate;
    public $paymentStatus = ''; // '' = semua, 'paid' = lunas, 'unpaid' = hutang
    public $paymentModal = false;
    public $paymentStockTransactionId;
    public $paymentAmount;
    public $paymentDate;
    public $paymentNote;
    public $detailTransaction;
    public $paymentHistory = [];
    public $paymentTotal = 0;
    public $paymentPaid = 0;
    public $paymentRemaining = 0;
    public $perPage = 10;

    public function mount($type)
    {
        $this->type = $type;
    }

    public function updatingSearch()
    {
        $this->resetPage(); // reset halaman saat search berubah
    }



    public function openPaymentModal($stockTransactionId)
    {
        $this->resetValidation();
        $this->paymentModal = true;
        $this->paymentStockTransactionId = $stockTransactionId;
        $this->paymentAmount = '';
        $this->paymentNote = '';
        $this->paymentDate = now()->format('Y-m-d');

        $stock = StockTransaction::with('cashTransactions')->findOrFail($stockTransactionId);

        $paid = $stock->cashTransactions->sum('amount');
        $total = $stock->total_amount;
        $remaining = $total - $paid;

        $this->paymentTotal = $total;
        $this->paymentPaid = $paid;
        $this->paymentRemaining = $remaining;

        $this->paymentHistory = $stock->cashTransactions
            ->where('transaction_type', 'payment')
            ->sortBy('transaction_date')
            ->values();
    }

    public function savePayment()
    {
        $this->validate([
            'paymentAmount' => 'required|numeric|min:1',
            'paymentDate' => 'required|date',
        ]);

        $stock = StockTransaction::with('cashTransactions')->findOrFail($this->paymentStockTransactionId);

        $totalPaid = $stock->cashTransactions()->sum('amount');
        $remaining = $stock->total - $totalPaid;

        if ($this->paymentAmount > $remaining) {
            $this->addError('paymentAmount', 'Jumlah melebihi sisa pembayaran.');
            return;
        }

        CashTransaction::create([
            'transaction_type' => 'payment',
            'stock_transaction_id' => $stock->id,
            'amount' => $this->paymentAmount,
            'transaction_date' => $this->paymentDate,
            'payment_method' => 'cash',
            'debt_credit' => $this->type,
            'note' => $this->paymentNote,
        ]);

        $newTotalPaid = $stock->cashTransactions()->sum('amount');
        $stock->is_fully_paid = $newTotalPaid >= $stock->total;
        $stock->save();

        $this->paymentModal = false;
        $this->dispatch('alert-success', ['message' => 'Pembarayan Berhasil.']);
    }

    public function render()
    {
        $query = CashTransaction::with(['stockTransaction.cashTransactions']);

        if ($this->type === 'utang') {
            $query->where('debt_credit', 'utang');
        } elseif ($this->type === 'piutang') {
            $query->where('debt_credit', 'piutang');
        }

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('note', 'like', "%{$this->search}%")
                    ->orWhere('reference_number', 'like', "%{$this->search}%")
                    ->orWhere('payment_method', 'like', "%{$this->search}%");
            });
        }

        if ($this->startDate) {
            $query->whereDate('transaction_date', '>=', Carbon::parse($this->startDate));
        }

        if ($this->endDate) {
            $query->whereDate('transaction_date', '<=', Carbon::parse($this->endDate));
        }

        if ($this->paymentStatus === 'paid') {
            $query->whereHas('stockTransaction', function ($q) {
                $q->where('is_fully_paid', true);
            });
        } elseif ($this->paymentStatus === 'unpaid') {
            $query->whereHas('stockTransaction', function ($q) {
                $q->where('is_fully_paid', false);
            });
        }

        $transactions = $query
            ->orderBy($this->orderBy, $this->orderDirection)
            ->paginate($this->perPage);

        return view('livewire.data-cash-transaction', compact('transactions'));
    }
}
