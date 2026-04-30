<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    protected $fillable = [
        'driver_id',
        'trip_type_id',
        'vehicle_brand_id',
        'vehicle_model_id',
        'color',
        'year',
        'plate_number',
        'vehicle_license_image',
        'car_front_image',
        'car_back_image',
        'car_left_image',
        'car_right_image',
        'isactive',
        'status',
        'rejection_reason',
    ];

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function tripType()
    {
        return $this->belongsTo(TripType::class);
    }

    public function model()
    {
        return $this->belongsTo(VehicleModel::class, 'vehicle_model_id');
    }
    
    public function brand()
    {
        return $this->belongsTo(VehicleBrand::class, 'vehicle_brand_id');
    }
}

