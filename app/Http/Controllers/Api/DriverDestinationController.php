<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DriverDestinationPreference;
use Illuminate\Http\Request;

class DriverDestinationController extends Controller
{
    public function index(Request $request)
    {
        $driver = $request->user();

        if ($driver->usertype !== 'driver') {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        $preference = DriverDestinationPreference::where('driver_id', $driver->id)->first();

        return response()->json([
            'status' => true,
            'data' => $preference,
        ]);
    }
    public function store(Request $request)
    {
        $driver = $request->user();

        if ($driver->usertype !== 'driver') {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'address' => 'nullable|string',
        ]);

        $preference = DriverDestinationPreference::updateOrCreate(
            ['driver_id' => $driver->id],
            [
                'lat' => $data['lat'],
                'lng' => $data['lng'],
                'address' => $data['address'] ?? null,
            ]
        );

        return response()->json([
            'status' => true,
            'message' => 'Destination preference saved successfully',
            'data' => $preference,
        ]);
    }

    /**
     * Remove driver's destination preference.
     */
    public function destroy(Request $request)
    {
        $driver = $request->user();

        DriverDestinationPreference::where('driver_id', $driver->id)->delete();

        return response()->json([
            'status' => true,
            'message' => 'Destination preference removed successfully',
        ]);
    }
}
