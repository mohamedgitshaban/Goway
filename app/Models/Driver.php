<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Driver extends User
{
    use SoftDeletes;

    /**
     * Relations rendered by DriverResource. Eager-load them to avoid N+1 queries.
     */
    public const RESOURCE_RELATIONS = [
        'wallet',
        'driverDocument.driver',
        'driverDocument.tripType',
        'vehicles.tripType',
        'vehicles.brand',
        'vehicles.model',
        'activeVehicle.tripType',
        'activeVehicle.brand',
        'activeVehicle.model',
    ];

    protected $table = 'users';
    protected static function booted()
    {
        static::creating(function ($model) {
            $model->usertype = User::ROLE_DRIVER;
            $model->name = $model->first_name . ' ' . $model->last_name;
        });
        static::created(function ($model) {
            $model->wallet()->create([
                'user_id' => $model->id,
                'balance' => 0,
            ]);
        });
        static::updating(function ($model) {
            $model->name = $model->first_name . ' ' . $model->last_name;
        });
        static::addGlobalScope('drivers', function ($query) {
            $query->where('usertype', User::ROLE_DRIVER);
        });
    }
    public function driverDocument()
    {
        return $this->hasOne(DriverDocument::class, 'user_id');
    }
    public function wallet()
    {
        return $this->hasOne(Wallet::class, 'user_id');
    }
    public function vehicles()
    {
        return $this->hasMany(Vehicle::class, 'driver_id');
    }

    public function activeVehicle()
    {
        return $this->hasOne(Vehicle::class, 'driver_id')->where('isactive', 1)->where('status','approved');
    }

    /**
     * The trip shown as "current_trip" in DriverResource.
     */
    public function activeTrip()
    {
        return $this->hasOne(Trip::class, 'driver_id')->where(function ($query) {
            $query->whereIn('status', ['pending', 'searching_driver', 'driver_assigned', 'driver_arrived', 'in_progress', 'completed'])
                ->orWhere(function ($query) {
                    $query->where('status', 'cancelled_by_client')->where('is_paid', false);
                });
        });
    }
}
