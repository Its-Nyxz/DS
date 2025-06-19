<?php

namespace App\Livewire;

use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\CashTransaction;

class DataCashFlow extends Component
{
    use WithPagination;

    public $search = '';
    public $startDate;
    public $endDate;
    public $showDetailModal = false;
    public $selectedTransaction;
    public $transactionDetail = [];
    public $perPage = 20;

    public function mount()
    {
        // Default: hari ini
        $this->startDate = Carbon::today()->format('Y-m-d');
        $this->endDate = Carbon::today()->format('Y-m-d');
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function showCashDetail($id)
    {
        $tx = CashTransaction::with('stockTransaction.cashTransactions')->findOrFail($id);

        $stock = $tx->stockTransaction;

        $tagihan = $stock?->total_amount ?? $tx->amount;
        $dibayar = $stock?->cashTransactions
            ->where('transaction_type', 'payment')
            ->sum('amount') ?? 0;
        $sisa = $tagihan - $dibayar;

        $pembayaran = $stock?->cashTransactions
            ->where('transaction_type', 'payment')
            ->sortBy('transaction_date')
            ->values() ?? collect();

        $this->selectedTransaction = $tx;
        $this->transactionDetail = [
            'is_stock' => $tx->transaction_type === 'stock',
            'is_payment' => $tx->transaction_type === 'payment',
            'transaction_code' => $stock?->transaction_code ?? '-',
            'reference' => $tx->reference_number ?? '-',
            'tanggal' => $tx->transaction_date,
            'metode' => $tx->payment_method,
            'note' => $tx->note,
            'tagihan' => $tagihan,
            'dibayar' => $dibayar,
            'sisa' => $sisa,
            'pembayaran' => $pembayaran,
        ];

        $this->showDetailModal = true;
    }

    public function render()
    {
        $query = CashTransaction::with('stockTransaction');

        // Filter tanggal
        if ($this->startDate) {
            $query->whereDate('transaction_date', '>=', Carbon::parse($this->startDate));
        }

        if ($this->endDate) {
            $query->whereDate('transaction_date', '<=', Carbon::parse($this->endDate));
        }

        // Filter pencarian
        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('note', 'like', '%' . $this->search . '%')
                    ->orWhere('reference_number', 'like', '%' . $this->search . '%');
            });
        }

        // Ambil data
        $transactions = $query->orderBy('transaction_date', 'asc')->get();

        // Format data debit/kredit
        $saldo = 0;
        $formatted = $transactions->map(function ($tx) use (&$saldo) {
            $debit = in_array($tx->debt_credit, ['piutang']) ? $tx->amount : 0;
            $kredit = in_array($tx->debt_credit, ['utang']) ? $tx->amount : 0;

            // Hitung saldo berjalan
            $saldo += $debit - $kredit;

            return [
                'id' => $tx->id,
                'tanggal' => Carbon::parse($tx->transaction_date)->format('d/m/Y'),
                'keterangan' => trim("{$tx->reference_number} - {$tx->note}") ?: '-',
                'debit' => $debit,
                'kredit' => $kredit,
                'transaction_code' => $tx->stockTransaction?->transaction_code ?? '',
                'saldo' => $saldo,
            ];
        });

        // Hitung total
        $totalDebit = $formatted->sum('debit');
        $totalKredit = $formatted->sum('kredit');
        $totalSaldo = $totalDebit - $totalKredit;

        $formatted->push([
            'tanggal' => 'Total',
            'keterangan' => '',
            'debit' => $totalDebit,
            'kredit' => $totalKredit,
            'saldo' => $totalSaldo,
        ]);

        return view('livewire.data-cash-flow', [
            'transactions' => $formatted,
        ]);
    }
}
