<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    /** @use HasFactory<\Database\Factories\ItemFactory> */
    use HasFactory;

    protected $table = "items";
    protected $guarded = ['id'];

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function conversions()
    {
        return $this->hasMany(UnitConversion::class);
    }

    public function transactions()
    {
        return $this->hasMany(StockTransactionItem::class);
    }

    public function opnames()
    {
        return $this->hasMany(StockOpname::class);
    }

    public function returns()
    {
        return $this->hasMany(Retur::class);
    }
}
