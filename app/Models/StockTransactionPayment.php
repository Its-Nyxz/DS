<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockTransactionPayment extends Model
{
    /** @use HasFactory<\Database\Factories\StockTransactionPaymentFactory> */
    use HasFactory;

    protected $table = "stock_transaction_payments";
    protected $guarded = ['id'];
    protected $casts = [
        'payment_date' => 'date',
    ];

    // Relasi ke transaksi induk
    public function transaction()
    {
        return $this->belongsTo(StockTransaction::class, 'stock_transaction_id');
    }

    // Relasi ke termin (opsional)
    public function schedule()
    {
        return $this->belongsTo(StockTransactionPaymentSchedule::class, 'payment_schedule_id');
    }

    // Relasi ke user yang membayar
    public function payer()
    {
        return $this->belongsTo(User::class, 'paid_by');
    }
}
