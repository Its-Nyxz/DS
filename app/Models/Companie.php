<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Companie extends Model
{
    /** @use HasFactory<\Database\Factories\CompanieFactory> */
    use HasFactory;

     protected $table = "companies";
    protected $guarded = ['id'];
}
