<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CashTransaction extends Model
{
    /** @use HasFactory<\Database\Factories\CashTransactionFactory> */
    use HasFactory, SoftDeletes;

    protected $table = "cash_transactions";
    protected $guarded = ['id'];

    public function stockTransaction()
    {
        return $this->belongsTo(StockTransaction::class);
    }
}
