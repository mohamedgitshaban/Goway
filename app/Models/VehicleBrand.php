<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VehicleBrand extends Model
{
    protected $fillable = [
        'trip_type_id',
        'name',
    ];

    public function tripType()
    {
        return $this->belongsTo(TripType::class, 'trip_type_id');
    }

    public function vehicleModels()
    {
        return $this->hasMany(VehicleModel::class, 'vehicle_brand_id');
    }

    public function vehicles()
    {
        return $this->hasMany(Vehicle::class, 'vehicle_brand_id');
    }
}
