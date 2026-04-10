<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Admin;
use App\Models\Permission;

class AdminUserSeeder extends Seeder
{
    public function run()
    {
        $this->command->info('Seeding initial admin user and permissions');

        // canonical permissions list (resource.action)
        $permissions = [
            // clients
            'clients.index','clients.export','clients.show','clients.activate','clients.suspend','clients.status_toggle','clients.destroy','clients.restore',
            // drivers
            'drivers.index','drivers.export','drivers.show','drivers.activate','drivers.suspend','drivers.status_toggle','drivers.destroy','drivers.restore',
            // admins
            'admins.index','admins.export','admins.show','admins.store','admins.update','admins.activate','admins.suspend','admins.status_toggle','admins.destroy','admins.restore',
            // trip types
            'trip_types.index','trip_types.export','trip_types.store','trip_types.update','trip_types.show','trip_types.activate','trip_types.suspend','trip_types.status_toggle','trip_types.licence_toggle','trip_types.destroy','trip_types.restore',
            // wallets
            'wallets.index','wallets.show',
            // documents
            'documents.index','documents.accept','documents.reject',
            // offers & coupons
            'offers.index','offers.store','offers.show','offers.update','offers.destroy',
            'coupons.index','coupons.store','coupons.show','coupons.update','coupons.destroy',
            // trips
            'trips.index',
        ];

        $permIds = [];
        foreach ($permissions as $name) {
            $p = Permission::firstOrCreate(['name' => $name], ['description' => null]);
            $permIds[] = $p->id;
        }

        // Create admin user if not exists
        $email = env('INITIAL_ADMIN_EMAIL', 'admin@aa.com');
        $phone = env('INITIAL_ADMIN_PHONE', '01234567890');
        $password = env('INITIAL_ADMIN_PASSWORD', 'password');

        $admin = Admin::where('email', $email)->orWhere('phone', $phone)->first();
        if (! $admin) {
            $admin = Admin::create([
                'first_name' => 'Super',
                'last_name' => 'Admin',
                'email' => $email,
                'phone' => $phone,
                'password' => Hash::make($password),
                'status' => 'active',
            ]);

            $this->command->info('Created admin: ' . $email . ' / ' . $phone);
        } else {
            $this->command->info('Admin already exists: ' . $admin->id);
        }

        // Attach all permissions to this admin
        $admin->syncPermissions($permIds);

        $this->command->info('Assigned ' . count($permIds) . ' permissions to admin id=' . $admin->id);
    }
}
