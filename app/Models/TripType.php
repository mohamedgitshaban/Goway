<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TripType extends Model
{
    use HasFactory , SoftDeletes;

    protected $fillable = [
        'id',
        'name_en',
        'name_ar',
        'image',
        'price_per_km',
        'max_distance',
        'profit_margin',
        'status',
        'need_licence',
    ];

    protected $casts = [
        'price_per_km' => 'decimal',
        'max_distance' => 'decimal',
        'profit_margin' => 'decimal',
        'need_licence' => 'boolean',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

}
