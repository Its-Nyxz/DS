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
    public $showFormModal = false;
    public $form = [
        'id' => null,
        'transaction_type' => '',
        'transaction_date' => '',
        'amount' => '',
        'payment_method' => '',
        'note' => '',
    ];
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

    public function openForm($id = null)
    {
        if ($id) {
            $tx = CashTransaction::findOrFail($id);

            $this->form = [
                'id' => $tx->id,
                'transaction_type' => $tx->transaction_type,
                'transaction_date' => Carbon::parse($tx->transaction_date)->format('Y-m-d\TH:i'),
                'amount' => $tx->amount,
                'payment_method' => $tx->payment_method,
                'note' => $tx->note,
            ];
        } else {
            $this->reset('form');
            $this->form['transaction_date'] = Carbon::now()->format('Y-m-d\TH:i'); // gunakan jam sekarang
            $this->form['payment_method'] = 'cash';
        }

        $this->showFormModal = true;
    }

    public function save()
    {
        $this->validate([
            'form.transaction_type' => 'required|string|in:stock,expense,payment,income,transfer_in,adjustment_in,refund_in',
            'form.transaction_date' => 'required|date_format:Y-m-d\TH:i',
            'form.amount' => 'required|numeric|min:0',
            'form.payment_method' => 'required|string|max:255',
            'form.note' => 'nullable|string',
        ]);

        $typeCode = [
            'income' => 'INC',
            'expense' => 'EXP',
            'payment' => 'PAY',
            'stock' => 'STK',
            'transfer_in' => 'TRF',
            'adjustment_in' => 'ADJ',
            'refund_in' => 'RFD',
        ];

        $methodCode = [
            'cash' => 'CSH',
            'transfer' => 'TRF',
            'qris' => 'QRS',
        ];

        $reference = $this->form['id']
            ? CashTransaction::find($this->form['id'])?->reference_number
            : ($typeCode[$this->form['transaction_type']] ?? 'REF') . '-' .
            ($methodCode[$this->form['payment_method']] ?? 'OTH') . '-' .
            now()->format('YmdHis');

        CashTransaction::updateOrCreate(
            ['id' => $this->form['id']],
            [
                'transaction_type' => $this->form['transaction_type'],
                'transaction_date' => Carbon::createFromFormat('Y-m-d\TH:i', $this->form['transaction_date']),
                'amount' => $this->form['amount'],
                'payment_method' => $this->form['payment_method'],
                'note' => $this->form['note'],
                'reference_number' => $reference,
            ]
        );

        $this->showFormModal = false;
        $this->dispatch('alert-success', ['message' => 'Berhasil Menyimpan.']);
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
            'trans_type' => $tx->transaction_type,
            'transaction_code' => $stock?->transaction_code ?? '-',
            'reference' => $tx->reference_number ?? '-',
            'tanggal' => $tx->transaction_date,
            'stock_id' => $tx->stock_transaction_id,
            'metode' => $tx->payment_method,
            'note' => $tx->note,
            'tagihan' => $tagihan,
            'dibayar' => $dibayar,
            'sisa' => $sisa,
            'pembayaran' => $pembayaran,
        ];

        $this->showDetailModal = true;
    }

    public function delete($id)
    {
        $tx = CashTransaction::findOrFail($id);
        $tx->delete();

        $this->dispatch('alert-success', ['message' => 'Transaksi berhasil dihapus.']);
    }

    public function exportPdf()
    {
        $query = CashTransaction::with('stockTransaction');

        if ($this->startDate) {
            $query->whereDate('transaction_date', '>=', Carbon::parse($this->startDate));
        }

        if ($this->endDate) {
            $query->whereDate('transaction_date', '<=', Carbon::parse($this->endDate));
        }

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('note', 'like', '%' . $this->search . '%')
                    ->orWhere('reference_number', 'like', '%' . $this->search . '%');
            });
        }

        $transactions = $query->orderBy('transaction_date', 'asc')->get();

        // Hitung saldo awal
        $getDebit = function ($tx) {
            return (
                in_array($tx->transaction_type, ['income', 'transfer_in']) ||
                ($tx->transaction_type === 'stock' && $tx->payment_method === 'cash' && $tx->debt_credit === 'piutang') ||
                ($tx->transaction_type === 'payment' && $tx->debt_credit === 'piutang')
            ) ? $tx->amount : 0;
        };

        $getKredit = function ($tx) {
            return (
                $tx->transaction_type === 'expense' ||
                ($tx->transaction_type === 'stock' && $tx->payment_method === 'cash' && $tx->debt_credit === 'utang') ||
                ($tx->transaction_type === 'payment' && $tx->debt_credit === 'utang')
            ) ? $tx->amount : 0;
        };

        $saldoAwal = CashTransaction::whereDate('transaction_date', '<', Carbon::parse($this->startDate))
            ->get()
            ->reduce(fn($carry, $tx) => $carry + ($getDebit($tx) - $getKredit($tx)), 0);

        $saldo = $saldoAwal;

        $data = collect([
            [
                'tanggal' => 'Saldo Awal',
                'keterangan' => '',
                'debit' => 0,
                'kredit' => 0,
                'saldo' => $saldoAwal,
            ]
        ]);

        $data = $data->merge(
            $transactions->map(function ($tx) use (&$saldo, $getDebit, $getKredit) {
                $debit = $getDebit($tx);
                $kredit = $getKredit($tx);
                $saldo += $debit - $kredit;

                return [
                    'tanggal' => Carbon::parse($tx->transaction_date)->format('d/m/Y H:i'),
                    'keterangan' => trim(collect([
                        $tx->stockTransaction?->transaction_code,
                        $tx->note,
                        $tx->stockTransaction?->supplier?->name
                            ? '(' . $tx->stockTransaction->supplier->name . ')'
                            : null,
                        $tx->stockTransaction?->customer?->name
                            ? '(' . $tx->stockTransaction->customer->name . ')'
                            : null,
                    ])->filter()->join(' ')) ?: '-',
                    'reference' => $tx->reference_number ?? '-',
                    'debit' => $debit,
                    'kredit' => $kredit,
                    'saldo' => $saldo,
                ];
            })
        );

        // Tambahkan total
        $totalDebit = $data->sum('debit');
        $totalKredit = $data->sum('kredit');
        $totalSaldo = $data->last()['saldo'];

        $data->push([
            'tanggal' => 'Total',
            'keterangan' => '',
            'reference' => '',
            'debit' => $totalDebit,
            'kredit' => $totalKredit,
            'saldo' => $totalSaldo,
        ]);

        $html = view('pdf.cash-flow-report', [
            'transactions' => $data,
            'start' => Carbon::parse($this->startDate)->format('d/m/Y'),
            'end' => Carbon::parse($this->endDate)->format('d/m/Y'),
        ])->render();

        $pdf = new \TCPDF();
        $pdf->SetMargins(10, 10, 10);
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 10);
        $pdf->writeHTML($html, true, false, true, false, '');

        $filename = 'laporan_arus_kas_' . now()->translatedFormat('d_F_Y') . '.pdf';

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
        $query = CashTransaction::with('stockTransaction')->where('amount', '!=', 0);

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

        // 🔁 Gunakan fungsi untuk menentukan nilai debit/kredit
        $getDebit = function ($tx) {
            return (
                in_array($tx->transaction_type, ['income', 'transfer_in']) ||
                ($tx->transaction_type === 'stock' && $tx->payment_method === 'cash' && $tx->debt_credit === 'piutang') ||
                ($tx->transaction_type === 'payment' && $tx->debt_credit === 'piutang')
            ) ? $tx->amount : 0;
        };

        $getKredit = function ($tx) {
            return (
                $tx->transaction_type === 'expense' ||
                ($tx->transaction_type === 'stock' && $tx->payment_method === 'cash' && $tx->debt_credit === 'utang') ||
                ($tx->transaction_type === 'payment' && $tx->debt_credit === 'utang')
            ) ? $tx->amount : 0;
        };

        // 💰 Saldo awal sebelum filter tanggal
        $saldoAwal = CashTransaction::whereDate('transaction_date', '<', Carbon::parse($this->startDate))
            ->get()
            ->reduce(fn($carry, $tx) => $carry + ($getDebit($tx) - $getKredit($tx)), 0);

        $saldo = $saldoAwal;
        $formatted = collect([
            [
                'tanggal' => 'Saldo Awal',
                'keterangan' => '',
                'debit' => 0,
                'kredit' => 0,
                'saldo' => $saldoAwal,
            ]
        ]);

        $formatted = $formatted->merge(
            $transactions->map(function ($tx) use (&$saldo, $getDebit, $getKredit) {
                $debit = $getDebit($tx);
                $kredit = $getKredit($tx);

                $saldo += $debit - $kredit;

                return [
                    'id' => $tx->id,
                    'tanggal' => Carbon::parse($tx->transaction_date)->format('d/m/Y H:i'),
                    'reference_number' => $tx->reference_number ?? '-',
                    'keterangan' => trim(collect([
                        $tx->stockTransaction?->transaction_code,
                        $tx->note,
                        $tx->stockTransaction?->supplier?->name
                            ? '(' . $tx->stockTransaction->supplier->name . ')'
                            : null,
                        $tx->stockTransaction?->customer?->name
                            ? '(' . $tx->stockTransaction->customer->name . ')'
                            : null,
                    ])->filter()->join(' ')) ?: '-',
                    'debit' => $debit,
                    'kredit' => $kredit,
                    'saldo' => $saldo,
                ];
            })
        );

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
