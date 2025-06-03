<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockTransactionItem extends Model
{
    /** @use HasFactory<\Database\Factories\StockTransactionItemFactory> */
    use HasFactory;

    protected $table = "stock_transaction_items";
    protected $guarded = ['id'];


    public function transaction()
    {
        return $this->belongsTo(StockTransaction::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
