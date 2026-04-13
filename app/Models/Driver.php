<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Driver extends User
{
    protected $table = 'users';
    protected static function booted()
    {
        static::creating(function ($model) {
            $model->usertype = User::ROLE_DRIVER;
            $model->name = $model->first_name . ' ' . $model->last_name;
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
        public function vehicles(){
            return $this->hasMany(Vehicle::class, 'driver_id');
        }
            
        
}
