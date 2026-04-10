<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TripType extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'id',
        'name_en',
        'name_ar',
        'base_fare',
        'code',
        'image',
        'price_per_km',
        'max_distance',
        'profit_margin',
        'status',
        'need_licence',
    ];

    protected $casts = [
        'price_per_km' => 'decimal:2',
        'max_distance' => 'decimal:2',
        'profit_margin' => 'decimal:2',
        'need_licence' => 'boolean',
    ];
    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];
    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];
    public function vehicleModels()
    {
        return $this->hasMany(VehicleModel::class);
    }
}
