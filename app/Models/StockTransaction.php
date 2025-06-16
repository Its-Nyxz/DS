<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StockTransaction extends Model
{
    /** @use HasFactory<\Database\Factories\StockTransactionFactory> */
    use HasFactory, SoftDeletes;

    protected $table = "stock_transactions";
    protected $guarded = ['id'];

    protected $casts = [
        'transaction_date' => 'datetime',
    ];


    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Relasi ke detail item transaksi
    public function items()
    {
        return $this->hasMany(StockTransactionItem::class);
    }

    public function stockOpnames()
    {
        return $this->hasMany(StockOpname::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function paymentSchedules()
    {
        return $this->hasMany(StockTransactionPaymentSchedule::class);
    }

    public function payments()
    {
        return $this->hasMany(StockTransactionPayment::class);
    }

    // Total yang sudah dibayar
    public function getTotalPaidAttribute()
    {
        return $this->payments()->sum('amount');
    }

    // Status lunas
    public function getIsFullyPaidAttribute()
    {
        return $this->paymentSchedules()->where('is_paid', false)->count() === 0;
    }
}
