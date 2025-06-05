<?php

namespace App\Models;

use App\Models\Retur;
use App\Models\ItemSupplier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Supplier extends Model
{
    /** @use HasFactory<\Database\Factories\SupplierFactory> */
    use HasFactory;

    protected $table = "suppliers";
    protected $guarded = ['id'];

    public function items()
    {
        return $this->belongsToMany(Item::class, 'item_suppliers')
            ->using(ItemSupplier::class)
            ->withPivot(['harga_beli', 'is_default', 'min_qty', 'lead_time_days', 'catatan', 'deleted_at'])
            ->withTimestamps();
    }

    public function transactions()
    {
        return $this->hasMany(StockTransaction::class);
    }

    public function returns()
    {
        return $this->hasMany(Retur::class);
    }
}
