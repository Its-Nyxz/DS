<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockTransactionItem extends Model
{
    /** @use HasFactory<\Database\Factories\StockTransactionItemFactory> */
    use HasFactory, SoftDeletes;

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

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function selectedUnit()
    {
        return $this->belongsTo(Unit::class, 'selected_unit_id');
    }
}
