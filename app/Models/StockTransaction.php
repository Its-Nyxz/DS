<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockTransaction extends Model
{
    /** @use HasFactory<\Database\Factories\StockTransactionFactory> */
    use HasFactory;

    protected $table = "stock_transactions";
    protected $guarded = ['id'];


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
}
