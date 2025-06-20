<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\StockTransaction;

class DebtNotification extends Component
{

    public $hutangPiutang = [];

    public function loadNotifications()
    {
        $belumLunas = StockTransaction::with('cashTransactions')
            ->where('is_fully_paid', false)
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($item) {
                $type = $item->type === 'in' ? 'Utang' : 'Piutang';

                return [
                    'title' => "Transaksi {$type} Belum Lunas",
                    'message' => "Kode: {$item->transaction_code}, Sisa: Rp " . number_format($item->total_amount - $item->cashTransactions->sum('amount'), 0, ',', '.'),
                    'url' => route('cashtransactions.index', ['type' => $type === 'Utang' ? 'utang' : 'piutang']),
                    'type' => $type,
                ];
            });

        $this->hutangPiutang = $belumLunas->toArray();
    }
    public function render()
    {
        return view('livewire.debt-notification');
    }
}
