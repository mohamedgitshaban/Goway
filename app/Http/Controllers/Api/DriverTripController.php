<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Events\DriverAssigned;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DriverTripController extends Controller
{
    public function accept(Request $request, Trip $trip)
    {
        $driver = $request->user();

        if (! $driver->isDriver()) {
            return response()->json(['status' => false, 'message' => 'Not a driver'], 403);
        }

        return DB::transaction(function () use ($trip, $driver) {

            if ($trip->status !== 'searching_driver' || $trip->driver_id !== null) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Trip already taken or not available',
                ], 422);
            }

            $trip->update([
                'driver_id' => $driver->id,
                'status' => 'driver_assigned',
                'driver_assigned_at' => now(),
            ]);

            broadcast(new DriverAssigned($trip));

            return response()->json([
                'status'  => true,
                'message' => 'Trip accepted',
                'trip_id' => $trip->id,
            ]);
        });
    }
}
