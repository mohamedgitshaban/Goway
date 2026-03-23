<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TripType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_en',
        'name_ar',
        'price_per_km',
        'profit_margin',
        'status',
    ];
}
