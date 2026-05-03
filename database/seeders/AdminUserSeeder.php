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
            'clients.index','clients.export','clients.show','clients.status_toggle','clients.destroy','clients.restore',
            // drivers
            'drivers.index','drivers.export','drivers.show','drivers.status_toggle','drivers.destroy','drivers.restore',
            // admins
            'admins.index','admins.export','admins.show','admins.store','admins.update','admins.status_toggle','admins.destroy','admins.restore',
            // trip types
            'trip_types.index','trip_types.export','trip_types.store','trip_types.update','trip_types.show','trip_types.status_toggle','trip_types.licence_toggle','trip_types.destroy','trip_types.restore',
            // wallets
            'wallets.index','wallets.show',
            // documents
            'documents.index','documents.accept','documents.reject','vehicles.index','vehicles.accept','vehicles.reject',
            'dashboard.index','dashboard.active_driver','dashboard.disactive_driver','dashboard.other_driver',
            'dashboard.active_vehicle','dashboard.trip_type','dashboard.completed_trip','dashboard.cancle_by_driver','dashboard.cancle_by_client'
            ,'dashboard.offers','dashboard.coupons',
            // offers & coupons
            'offers.index','offers.store','offers.show','offers.update','offers.destroy',
            'coupons.index','coupons.store','coupons.show','coupons.update','coupons.destroy',
            // trips
            'trips.index','roles.index','roles.store', 'roles.show', 'roles.update', 'roles.destroy', 'roles.restore',
        ];

        $permIds = [];
        foreach ($permissions as $name) {
            $p = Permission::firstOrCreate(['name' => $name], ['description' => null]);
            $permIds[] = $p->id;
        }
    }
}
