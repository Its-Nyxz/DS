<?php

namespace App\Livewire;

use Carbon\Carbon;
use Livewire\Component;
use Illuminate\Support\Str;
use Livewire\WithPagination;
use App\Models\CashTransaction;
use App\Models\StockTransaction;
use Illuminate\Pagination\LengthAwarePaginator;

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
    public $showDetailModal = false;
    public $selectedTransaction;


    public function mount($type)
    {
        $this->type = $type;
        // Set default date filter to today
        $this->startDate = Carbon::today()->format('Y-m-d');
        $this->endDate = Carbon::today()->format('Y-m-d');
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
        $this->paymentDate = now()->format('Y-m-d\TH:i');

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
            'paymentDate' => 'required|date_format:Y-m-d\TH:i',
        ]);

        $stock = StockTransaction::with('cashTransactions')->findOrFail($this->paymentStockTransactionId);

        $total = $stock->total_amount;

        // Hitung total sudah dibayar dari transaksi payment saja (bukan termasuk transaksi stock)
        $totalPaid = $stock->cashTransactions()
            ->where('transaction_type', 'payment')
            ->sum('amount');

        $remaining = $total - $totalPaid;

        if ($this->paymentAmount > $remaining) {
            $this->addError('paymentAmount', 'Jumlah melebihi sisa pembayaran.');
            return;
        }

        $expectedTotalPaid = $totalPaid + $this->paymentAmount;
        $isFullyPaid = $expectedTotalPaid >= $total;

        $shortCode = $stock->transaction_code
            ? 'TX' . substr($stock->transaction_code, -6)
            : 'STK' . $stock->id;

        $referenceNumber = $shortCode . '-PAY-' . now()->format('ymd') . '-' . strtoupper(Str::random(4));

        // Simpan transaksi pembayaran
        CashTransaction::create([
            'transaction_type' => 'payment',
            'stock_transaction_id' => $stock->id,
            'amount' => $this->paymentAmount,
            'transaction_date' => Carbon::parse($this->paymentDate),
            'reference_number' => $referenceNumber,
            'payment_method' => $isFullyPaid ? 'cash' : 'term',
            'debt_credit' => $this->type,
            'note' => $this->paymentNote,
        ]);

        // Update status pelunasan di stock_transaction
        $stock->is_fully_paid = $isFullyPaid;
        $stock->fully_paid_at = $isFullyPaid ? now() : null;
        $stock->save();

        // Update transaksi kas awal (stock) jika sudah lunas
        $initial = $stock->cashTransactions()
            ->where('transaction_type', 'stock')
            ->first();

        if ($initial) {
            $initial->note = $isFullyPaid
                ? ($stock->type === 'in' ? 'Pembelian' : 'Penjualan') . ' sudah Lunas'
                : 'Cicilan ' . ($stock->type === 'in' ? 'Pembelian' : 'Penjualan');
            $initial->save();
        }

        $this->paymentModal = false;
        $this->dispatch('alert-success', ['message' => 'Pembayaran berhasil.']);
    }

    public function showDetail($id)
    {
        $this->resetValidation();
        $this->selectedTransaction = CashTransaction::with('stockTransaction')->findOrFail($id);
        $this->showDetailModal = true;
    }

    public function exportPdf()
    {
        // Ambil data sesuai filter aktif
        $query = CashTransaction::with(['stockTransaction.cashTransactions'])
            ->where('transaction_type', 'stock')
            ->whereNotNull('stock_transaction_id');

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
            $query->whereHas('stockTransaction', fn($q) => $q->where('is_fully_paid', true));
        } elseif ($this->paymentStatus === 'unpaid') {
            $query->whereHas('stockTransaction', fn($q) => $q->where('is_fully_paid', false));
        }

        $transactions = $query->orderBy($this->orderBy, $this->orderDirection)->get();

        // Proses hitung paid dan remaining tiap transaksi
        foreach ($transactions as $tx) {
            $tx->paid = $tx->stockTransaction?->cashTransactions
                ->where('amount', '>', 0)
                ->sum('amount') ?? 0;

            $tx->total = $tx->stockTransaction?->total_amount ?? $tx->amount;
            $tx->remaining = $tx->total - $tx->paid;
        }

        $typeLabel = ucfirst($this->type);
        $startFormatted = Carbon::parse($this->startDate)->format('d-m-Y');
        $endFormatted = Carbon::parse($this->endDate)->format('d-m-Y');

        $html = view('pdf.cash-transactions', [
            'transactions' => $transactions,
            'title' => "Laporan Kas {$typeLabel} ({$startFormatted} - {$endFormatted})",
        ])->render();

        $pdf = new \TCPDF();
        $pdf->SetMargins(10, 10, 10);
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 10);
        $pdf->writeHTML($html, true, false, true, false, '');

        $filename = "laporan_kas_{$this->type}_{$startFormatted}_{$endFormatted}.pdf";

        return response()->stream(
            fn() => $pdf->Output($filename, 'I'),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => "inline; filename={$filename}",
            ]
        );
    }


    public function render()
    {
        $query = CashTransaction::with(['stockTransaction.cashTransactions'])
            ->where('transaction_type', 'stock') // hanya ambil transaksi kas awal (stock)
            ->whereNotNull('stock_transaction_id');

        // Filter jenis utang/piutang
        if ($this->type === 'utang') {
            $query->where('debt_credit', 'utang');
        } elseif ($this->type === 'piutang') {
            $query->where('debt_credit', 'piutang');
        }

        // Filter pencarian
        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('note', 'like', "%{$this->search}%")
                    ->orWhere('reference_number', 'like', "%{$this->search}%")
                    ->orWhere('payment_method', 'like', "%{$this->search}%");
            });
        }

        // Filter tanggal
        if ($this->startDate) {
            $query->whereDate('transaction_date', '>=', Carbon::parse($this->startDate));
        }

        if ($this->endDate) {
            $query->whereDate('transaction_date', '<=', Carbon::parse($this->endDate));
        }

        // Filter status pembayaran
        if ($this->paymentStatus === 'paid') {
            $query->whereHas('stockTransaction', fn($q) => $q->where('is_fully_paid', true));
        } elseif ($this->paymentStatus === 'unpaid') {
            $query->whereHas('stockTransaction', fn($q) => $q->where('is_fully_paid', false));
        }

        // Hanya tampilkan transaksi kas stock yang memiliki pembayaran (payment) terkait
        // $query->whereHas('stockTransaction.cashTransactions', function ($q) {
        //     $q->where('transaction_type', 'payment');
        // });

        // Ambil hasil
        $transactions = $query
            ->orderBy($this->orderBy, $this->orderDirection)
            ->paginate($this->perPage);

        return view('livewire.data-cash-transaction', compact('transactions'));
    }
}
