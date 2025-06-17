<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Carbon;
use App\Models\StockTransactionPaymentSchedule;

class DueDateNotification extends Component
{
    public $notifications = [];
    public $unreadCount = 0;

    public function mount()
    {
        $this->loadNotifications();
    }

    public function loadNotifications()
    {
        // Mengambil termin yang jatuh tempo dalam 7 hari ke depan dan belum dibayar
        $sevenDaysFromNow = Carbon::now()->addDays(7);

        $this->notifications = StockTransactionPaymentSchedule::with(['transaction' => function ($query) {
            // Exclude soft-deleted transactions
            $query->whereNull('deleted_at');
        }])
            ->where('due_date', '>', Carbon::now())  // mencari yang belum lewat
            ->where('due_date', '<=', $sevenDaysFromNow)  // mencari yang jatuh tempo dalam 7 hari ke depan
            ->where('is_paid', false)  // memastikan statusnya belum dibayar
            ->orderBy('due_date', 'asc')
            ->take(10)
            ->get()
            ->map(function ($schedule) {
                // Check if transaction exists and is not soft-deleted
                if ($schedule->transaction) {
                    $transactionType = $schedule->transaction->type ?? 'unknown';
                    $transactionCode = $schedule->transaction->transaction_code ?? 'Tidak Diketahui';

                    // Generate the notification object
                    return (object) [
                        'id' => $schedule->id,
                        'read_at' => null,  // opsional jika ingin menggunakan fitur read/unread
                        'data' => [
                            'title' => 'Termin Belum Dibayar',
                            'message' => 'No Transaksi: ' . $transactionCode .
                                ' - Jatuh Tempo: ' . Carbon::parse($schedule->due_date)->translatedFormat('d M Y'),
                            'url' => route('transactions.index', ['type' => $transactionType, 'id' => $schedule->stock_transaction_id]), // Correct URL format
                        ],
                    ];
                }

                return null; // Return null if transaction is soft-deleted
            })
            ->filter()  // Remove any null values (transactions that are soft-deleted)
            ->values(); // Re-index the collection after filtering out nulls

        // Debugging: Check notifications data
        // dd($this->notifications);

        $this->unreadCount = $this->notifications->count();  // jumlah notifikasi yang belum dibaca
    }



    public function markAsRead($id = null)
    {
        // Untuk dummy: hapus semua, atau satu saja
        if ($id) {
            $this->notifications = $this->notifications->reject(fn($n) => $n->id == $id);
        } else {
            $this->notifications = collect();
        }
        $this->unreadCount = $this->notifications->count();
    }

    public function render()
    {
        return view('livewire.due-date-notification');
    }
}
