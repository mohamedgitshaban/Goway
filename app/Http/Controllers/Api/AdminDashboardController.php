<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Driver;
use App\Models\Offer;
use App\Models\Trip;
use App\Models\TripType;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $permissions = $user->role ? $user->role->permissions->pluck('name')->toArray() : [];

        $data = [];

        if (in_array('dashboard.active_driver', $permissions)) {
            $data['active_drivers_count'] = Driver::where('status', 'active')->count();
        }

        if (in_array('dashboard.disactive_driver', $permissions)) {
            $data['inactive_drivers_count'] = Driver::where('status', 'disactive')->count();
        }

        if (in_array('dashboard.other_driver', $permissions)) {
            $data['other_drivers_count'] = Driver::whereNotIn('status', ['active', 'disactive'])->count();
        }

        if (in_array('dashboard.active_vehicle', $permissions)) {
            $data['active_vehicles_count'] = Vehicle::where('status', 'active')->count();
        }

        if (in_array('dashboard.trip_type', $permissions)) {
            $data['trip_types_count'] = TripType::count();
        }

        if (in_array('dashboard.completed_trip', $permissions)) {
            $data['completed_trips_count'] = Trip::where('status', 'completed')->count();
        }

        if (in_array('dashboard.cancle_by_client', $permissions)) {
            $data['cancelled_by_client_trips_count'] = Trip::where('status', 'cancelled_by_client')->count();
        }

        if (in_array('dashboard.cancle_by_driver', $permissions)) {
            $data['cancelled_by_driver_trips_count'] = Trip::where('status', 'cancelled_by_driver')->count();
        }

        if (in_array('dashboard.offers', $permissions)) {
            $data['active_offers_count'] = Offer::where('is_active', 1)->count();
        }

        if (in_array('dashboard.coupons', $permissions)) {
            $data['active_coupons_count'] = Coupon::where('ends_at', '>', now())->where('is_active', 1)->count();
        }

        return response()->json($data);
    }
}
