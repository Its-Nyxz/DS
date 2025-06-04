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
        return $this->belongsTo(StockTransaction::class, 'stock_transaction_id');
    }

    public function itemSupplier()
    {
        return $this->belongsTo(ItemSupplier::class, 'item_supplier_id');
    }
}
