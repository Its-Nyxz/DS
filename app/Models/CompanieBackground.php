<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanieBackground extends Model
{
    /** @use HasFactory<\Database\Factories\CompanieBackgroundFactory> */
    use HasFactory;

    protected $table = "companie_backgrounds";
    protected $guarded = ['id'];

    public function company()
    {
        return $this->belongsTo(Companie::class, 'companie_id');
    }
}
