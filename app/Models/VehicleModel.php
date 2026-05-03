<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VehicleModel extends Model
{
    protected $fillable = [
        'vehicle_brand_id',
        'name',
        'trip_type_id',
        'min_year',
        'max_year',
    ];

    // models belong to a brand (brand carries trip_type)
    public function brand()
    {
        return $this->belongsTo(VehicleBrand::class, 'vehicle_brand_id');
    }
}

