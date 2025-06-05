<?php

namespace App\Models;

use App\Models\ItemSupplier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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

    public function suppliers()
    {
        return $this->belongsToMany(Supplier::class, 'item_suppliers')
            ->using(ItemSupplier::class)
            ->withPivot(['harga_beli', 'is_default', 'min_qty', 'lead_time_days', 'catatan'])
            ->withTimestamps();
    }
}
