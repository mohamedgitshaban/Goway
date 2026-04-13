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
        $activeDriversCount = Driver::where('status', 'active')->count();
        $inactiveDriversCount = Driver::where('status', 'disactive')->count();
        $otherDriversCount = Driver::whereNotIn('status', ['active', 'disactive'])->count();
        $activeVehiclesCount = Vehicle::where('status', 'active')->count();
        $tripTypesCount = TripType::count();
        $completedTripsCount = Trip::where('status', 'completed')->count();
        $cancelledByClientTripsCount = Trip::where('status', 'cancelled_by_client')->count();
        $cancelledByDriverTripsCount = Trip::where('status', 'cancelled_by_driver')->count();
        $activeOffersCount = Offer::where('is_active', 1)->count();
        $activeCouponsCount = Coupon::where('ends_at', '>', now())->where('is_active', 1)->count();
        return response()->json([
            'active_drivers_count' => $activeDriversCount,
            'inactive_drivers_count' => $inactiveDriversCount,
            'other_drivers_count' => $otherDriversCount,
            'active_vehicles_count' => $activeVehiclesCount,
            'trip_types_count' => $tripTypesCount,
            'completed_trips_count' => $completedTripsCount,
            'cancelled_by_client_trips_count' => $cancelledByClientTripsCount,
            'cancelled_by_driver_trips_count' => $cancelledByDriverTripsCount,
            'active_offers_count' => $activeOffersCount,
            'active_coupons_count' => $activeCouponsCount,
        ]);
    }
}
