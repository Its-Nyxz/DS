<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemSupplier extends Model
{
    /** @use HasFactory<\Database\Factories\ItemSupplierFactory> */
    use HasFactory;

    protected $table = "item_suppliers";
    protected $guarded = ['id'];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Relasi ke transaksi stok (jika pakai item_supplier_id)
     */
    public function stockTransactions()
    {
        return $this->hasMany(StockTransactionItem::class);
    }

    /**
     * Relasi ke stok opname (jika pakai item_supplier_id)
     */
    public function stockOpnames()
    {
        return $this->hasMany(StockOpname::class);
    }

    /**
     * Relasi ke retur barang (jika pakai item_supplier_id)
     */
    public function returns()
    {
        return $this->hasMany(Retur::class);
    }

    public function conversions()
    {
        return $this->hasMany(UnitConversion::class);
    }
}
