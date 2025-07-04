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

    public function cashTransactions()
    {
        return $this->hasMany(CashTransaction::class, 'stock_transaction_id'); // Pastikan 'stock_transaction_id' adalah kolom foreign key
    }
}
