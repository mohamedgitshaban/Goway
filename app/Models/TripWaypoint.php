<?php
// app/Models/TripWaypoint.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TripWaypoint extends Model
{
    protected $fillable = [
        'trip_id',
        'order',
        'lat',
        'lng',
        'address',
    ];

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }
}
