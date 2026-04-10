<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
    ];

    public function admins()
    {
        return $this->belongsToMany(User::class, 'role_permissions', 'permission_id', 'admin_id')
            ->withPivot('can_edit')
            ->withTimestamps();
    }
}
