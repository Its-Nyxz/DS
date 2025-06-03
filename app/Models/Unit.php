<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    /** @use HasFactory<\Database\Factories\UnitFactory> */
    use HasFactory;

    protected $table = "units";
    protected $guarded = ['id'];

    public function items()
    {
        return $this->hasMany(Item::class);
    }
}
