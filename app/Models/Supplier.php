<?php

namespace App\Models;

use App\Models\Retur;
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
        return $this->hasMany(Item::class);
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
