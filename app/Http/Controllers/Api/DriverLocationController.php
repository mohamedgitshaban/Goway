<?php

namespace App\Http\Controllers\Api;

use App\Events\DriverLocationUpdated;
use App\Http\Controllers\Controller;
use App\Support\GeoHash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class DriverLocationController extends Controller
{
    private const DRIVER_LOCATION_GEOHASH_PRECISION = 5;

    public function update(Request $request)
    {
        $driver = $request->user();

        /*
        |--------------------------------------------------------------------------
        | 1. Validation & Authorization
        |--------------------------------------------------------------------------
        */
        if (!$driver->isDriver() || $driver->status !== 'active') {
            return response()->json([
                'status'  => false,
                'message' => !$driver->isDriver() ? 'Not a driver' : 'Driver not active'
            ], 403);
        }
        if (!$driver->is_online) {
            return response()->json([
                'status'  => false,
                'message' => 'Driver is offline. Please go online to update location.'
            ], 403);
        }
        $data = $request->validate([
            'lat'     => 'required|numeric',
            'lng'     => 'required|numeric',
            'bearing' => 'nullable|numeric',
            'speed'   => 'nullable|numeric',
        ]);

        $lat = (float) $data['lat'];
        $lng = (float) $data['lng'];

        /*
        |--------------------------------------------------------------------------
        | 2. Retrieve Previous State
        |--------------------------------------------------------------------------
        */
        $driverKey = "driver:{$driver->id}:location";

        // hmget returns an array of values, indexed sequentially
        $oldState   = Redis::hmget($driverKey, ['lat', 'lng', 'geohash']);
        $oldLat     = $oldState[0] ?? null;
        $oldLng     = $oldState[1] ?? null;
        $oldGeohash = $oldState[2] ?? null;

        /*
        |--------------------------------------------------------------------------
        | 3. Check for Unchanged Location
        |--------------------------------------------------------------------------
        */
        if ($oldLat !== null && $oldLng !== null && (float)$oldLat === $lat && (float)$oldLng === $lng) {
            Redis::expire($driverKey, 20); // Only extend TTL
            return response()->json([
                'status'  => true,
                'message' => 'Location unchanged — no event broadcasted'
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | 4. Compute New State & Dispatch Events
        |--------------------------------------------------------------------------
        */
        $newGeohash = GeoHash::encode($lat, $lng, self::DRIVER_LOCATION_GEOHASH_PRECISION);

        // Scenario A: First time entering a location (No old geohash)
        if (!$oldGeohash) {
            $this->addDriverToGeohash($driver->id, $newGeohash);
            $this->broadcastLocation($driver->id, $lat, $lng, $newGeohash, 'driver_entered', $data['bearing'] ?? null, $data['speed'] ?? null);
        }
        // Scenario B: Moved to a DIFFERENT geohash
        else if ($oldGeohash !== $newGeohash) {
            $this->removeDriverFromGeohash($driver->id, $oldGeohash);
            $this->broadcastLocation($driver->id, (float)$oldLat, (float)$oldLng, $oldGeohash, 'driver_left');

            $this->addDriverToGeohash($driver->id, $newGeohash);
            $this->broadcastLocation($driver->id, $lat, $lng, $newGeohash, 'driver_entered', $data['bearing'] ?? null, $data['speed'] ?? null);
        } elseif ($lat != $oldLat || $lng != $oldLng) {
            // Update location without changing geohash (e.g., moved within the same geohash)
            $this->addDriverToGeohash($driver->id, $newGeohash); // Ensure presence in set
            $this->broadcastLocation($driver->id, $lat, $lng, $newGeohash, 'driver_moved', $data['bearing'] ?? null, $data['speed'] ?? null);
        }
        // Scenario C: Moved within the SAME geohash
        else {
        }

        /*
        |--------------------------------------------------------------------------
        | 5. Persist State
        |--------------------------------------------------------------------------
        */
        Redis::hmset($driverKey, [
            'lat'        => $lat,
            'lng'        => $lng,
            'geohash'    => $newGeohash,
            'updated_at' => now()->toIso8601String(),
        ]);
        Redis::expire($driverKey, 20);

        return response()->json(['status' => true]);
    }

    /**
     * Add driver to geohash set
     */
    private function addDriverToGeohash(int $driverId, string $geohash): void
    {
        Redis::sadd("geohash:drivers:{$geohash}", $driverId);
    }

    /**
     * Remove driver from geohash set
     */
    private function removeDriverFromGeohash(int $driverId, string $geohash): void
    {
        Redis::srem("geohash:drivers:{$geohash}", $driverId);
    }

    /**
     * Dispatch DriverLocationUpdated event
     */
    private function broadcastLocation(int $driverId, float $lat, float $lng, string $geohash, string $eventType, ?float $bearing = null, ?float $speed = null): void
    {
        broadcast(new DriverLocationUpdated(
            driverId: $driverId,
            lat: $lat,
            lng: $lng,
            geohash: $geohash,
            eventType: $eventType,
            bearing: $bearing,
            speed: $speed
        ));
    }
}
