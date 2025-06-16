<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockTransactionPaymentSchedule extends Model
{
    /** @use HasFactory<\Database\Factories\StockTransactionPaymentScheduleFactory> */
    use HasFactory;

    protected $table = "stock_transaction_payment_schedules";
    protected $guarded = ['id'];

    // Relasi ke transaksi
    public function transaction()
    {
        return $this->belongsTo(StockTransaction::class, 'stock_transaction_id');
    }

    // Relasi ke pembayaran
    public function payments()
    {
        return $this->hasMany(StockTransactionPayment::class, 'payment_schedule_id');
    }

    // Accessor: total pembayaran untuk termin ini
    public function getPaidAmountAttribute()
    {
        return $this->payments()->sum('amount');
    }

    // Accessor: status lunas (bisa override is_paid manual)
    public function getIsFullyPaidAttribute()
    {
        return $this->paid_amount >= $this->scheduled_amount;
    }
}
