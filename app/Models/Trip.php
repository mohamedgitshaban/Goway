<?php
// app/Models/Trip.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Trip extends Model
{
    protected $fillable = [
        'client_id',
        'driver_id',
        'trip_type_id',
        'status',
        'payment_method',
        'distance_km',
        'base_fare',
        'price_per_km',
        'original_price',
        'discount_amount',
        'final_price',
        'offer_id',
        'coupon_id',
        'negotiation_enabled',
        'negotiated_price_before',
        'negotiated_price_after',
        'origin_lat',
        'origin_lng',
        'origin_address',
        'destination_lat',
        'destination_lng',
        'destination_address',
        'driver_assigned_at',
        'driver_arrived_at',
        'started_at',
        'completed_at',
        'cancelled_at',
        'cancelled_by',
        'cancel_reason',
        'cancel_description',
    ];

    protected $casts = [
        'negotiation_enabled' => 'boolean',
        'driver_assigned_at'  => 'datetime',
        'driver_arrived_at'   => 'datetime',
        'started_at'          => 'datetime',
        'completed_at'        => 'datetime',
        'cancelled_at'        => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }

    public function tripType()
    {
        return $this->belongsTo(TripType::class);
    }

    public function waypoints()
    {
        return $this->hasMany(TripWaypoint::class);
    }

    public function offer()
    {
        return $this->belongsTo(Offer::class);
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }
}
