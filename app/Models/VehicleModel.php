<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VehicleModel extends Model
{
    protected $fillable = [
        'trip_type_id',
        'name',
        'min_year',
        'max_year',
    ];

    public function tripType()
    {
        return $this->belongsTo(TripType::class);
    }
}

