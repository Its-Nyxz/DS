<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashTransaction extends Model
{
    /** @use HasFactory<\Database\Factories\CashTransactionFactory> */
    use HasFactory;

    protected $table = "cash_transactions";
    protected $guarded = ['id'];

    public function stockTransaction()
    {
        return $this->belongsTo(StockTransaction::class);
    }
}
