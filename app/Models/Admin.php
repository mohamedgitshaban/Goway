<?php

namespace App\Models;

class Admin extends User
{
    protected $table = 'users';

    protected $fillable = [
        'role_id',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            $model->usertype = User::ROLE_ADMIN;

            $model->name = $model->first_name . ' ' . $model->last_name;
        });
        static::updating(function ($model) {
            $model->name = $model->first_name . ' ' . $model->last_name;
        });
        static::addGlobalScope('admin', function ($query) {
            $query->where('usertype', User::ROLE_ADMIN);
        });
    }
}
