<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TripSafetyLocation extends Model
{
    protected $fillable = [
        'trip_id',
        'user_id',
        'lat',
        'lng',
        'accuracy',
        'speed',
        'heading',
        'recorded_at',
    ];

    protected $casts = [
        'lat' => 'float',
        'lng' => 'float',
        'accuracy' => 'float',
        'speed' => 'float',
        'heading' => 'float',
        'recorded_at' => 'datetime',
    ];

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
