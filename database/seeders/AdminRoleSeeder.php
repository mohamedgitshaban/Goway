<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Admin;

class AdminRoleSeeder extends Seeder
{
    public function run()
    {
        // Create or get the admin role
        $role = Role::firstOrCreate(
            ['name_en' => 'admin'],
            ['name_ar' => 'مشرف']
        );

        // Get all permission IDs
        $permissionIds = Permission::pluck('id')->toArray();

        // Assign all permissions to the admin role
        $role->permissions()->sync($permissionIds);

        // Assign this role to all admins
        Admin::query()->update(['role_id' => $role->id]);
    }
}
