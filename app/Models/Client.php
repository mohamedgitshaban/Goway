<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends User
{
    protected $table = 'users';
    protected static function booted()
    {
        static::creating(function ($model) {
            $model->usertype = User::ROLE_CLIENT;
            $model->name = $model->first_name . ' ' . $model->last_name;
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

}
