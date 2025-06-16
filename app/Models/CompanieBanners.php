<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanieBanners extends Model
{
    /** @use HasFactory<\Database\Factories\CompanieBannersFactory> */
    use HasFactory;
    protected $table = "companie_banners";
    protected $guarded = ['id'];

    public function company()
    {
        return $this->belongsTo(Companie::class, 'companie_id');
    }
}
