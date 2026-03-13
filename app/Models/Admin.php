<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Admin extends User
{
    protected $table = 'users';
    protected static function booted()
    {
        static::creating(function ($model) {
            $model->usertype = User::ROLE_ADMIN;
        });
        static::addGlobalScope('admin', function ($query) {
            $query->where('usertype', User::ROLE_ADMIN);
        });
    }
}
