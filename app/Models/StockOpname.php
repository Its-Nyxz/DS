<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StockOpname extends Model
{
    /** @use HasFactory<\Database\Factories\StockOpnameFactory> */
    use HasFactory, SoftDeletes;

    protected $table = "stock_opnames";
    protected $guarded = ['id'];


    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    // Relasi ke StockTransaction
    public function stockTransaction()
    {
        return $this->belongsTo(StockTransaction::class, 'stock_transaction_id');
    }
}
