<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TripType extends Model
{
    use HasFactory , SoftDeletes;

    protected $fillable = [
        'name_en',
        'name_ar',
        'image',
        'price_per_km',
        'max_distance',
        'profit_margin',
        'status',
    ];
}
