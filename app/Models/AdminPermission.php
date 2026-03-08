<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminPermission extends Model
{
    use HasFactory;

    protected $table = 'admin_permissions';

    protected $fillable = [
        'admin_id',
        'permission_id',
        'can_edit',
    ];

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function permission()
    {
        return $this->belongsTo(Permission::class);
    }
}
