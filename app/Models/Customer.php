<?php

namespace App\Models;

use App\Models\Retur;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Customer extends Model
{
    /** @use HasFactory<\Database\Factories\CustomerFactory> */
    use HasFactory;

    protected $table = "customers";
    protected $guarded = ['id'];

    public function returns()
    {
        return $this->hasMany(Retur::class);
    }
}
