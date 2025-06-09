<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnitConversion extends Model
{
    /** @use HasFactory<\Database\Factories\UnitConversionFactory> */
    use HasFactory;

    protected $table = "unit_conversions";
    protected $guarded = ['id'];
    protected $fillable = ['from_unit_id', 'to_unit_id', 'factor', 'item_supplier_id'];

    public function itemSupplier()
    {
        return $this->belongsTo(ItemSupplier::class);
    }

    public function fromUnit()
    {
        return $this->belongsTo(Unit::class, 'from_unit_id');
    }

    public function toUnit()
    {
        return $this->belongsTo(Unit::class, 'to_unit_id');
    }
}
