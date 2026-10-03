<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends User
{
        use SoftDeletes;
    protected $table = 'users';
    protected static function booted()
    {
        static::creating(function ($model) {
            $model->usertype = User::ROLE_CLIENT;
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
        static::addGlobalScope('clients', function ($query) {
            $query->where('usertype', User::ROLE_CLIENT);
        });
    }
    public function favoriteLocations()
    {
        return $this->hasMany(FavoriteLocation::class , 'user_id');
    }
    public function wallet()
    {
        return $this->hasOne(Wallet::class, 'user_id');
    }

    /**
     * The trip shown as "current_trip" in ClientResource.
     */
    public function activeTrip()
    {
        return $this->hasOne(Trip::class, 'client_id')
            ->whereIn('status', ['pending', 'searching_driver', 'driver_assigned', 'driver_arrived', 'in_progress']);
    }

}
