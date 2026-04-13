<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Events\DriverLocationUpdated;
use App\Support\GeoHash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class DriverLocationController extends Controller
{
    public function update(Request $request)
    {
        $driver = $request->user();

        if (! $driver->isDriver() ) {
            return response()->json(['status' => false, 'message' => 'Not a driver'], 403);
        }
        if ($driver->status !== 'active') {
            return response()->json(['status' => false, 'message' => 'Driver not active'], 403);
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
        | 0) Check if location unchanged → no event
        |--------------------------------------------------------------------------
        */
        $oldLat = Redis::hget("driver:{$driver->id}:location", 'lat');
        $oldLng = Redis::hget("driver:{$driver->id}:location", 'lng');
        $oldGeohash = Redis::hget("driver:{$driver->id}:location", 'geohash');

        if ($oldLat && $oldLng) {
            if ((float)$oldLat === $lat && (float)$oldLng === $lng) {

                // Update timestamp only
                Redis::hmset("driver:{$driver->id}:location", [
                    'lat'        => $lat,
                    'lng'        => $lng,
                    'geohash'    => $oldGeohash,
                    'updated_at' => now()->toIso8601String(),
                ]);

                Redis::expire("driver:{$driver->id}:location", 20);

                return response()->json([
                    'status'  => true,
                    'message' => 'Location unchanged — no event broadcasted'
                ]);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 1) Compute new geohash
        |--------------------------------------------------------------------------
        */
        $newGeohash = GeoHash::encode($lat, $lng, 7);

        /*
        |--------------------------------------------------------------------------
        | 2) Driver moved to a NEW geohash → left + entered
        |--------------------------------------------------------------------------
        */
        if ($oldGeohash && $oldGeohash !== $newGeohash) {

            // Remove from old geohash
            Redis::srem("geohash:drivers:{$oldGeohash}", $driver->id);

            // Broadcast driver_left
            broadcast(new DriverLocationUpdated(
                driverId: $driver->id,
                lat: $lat,
                lng: $lng,
                geohash: $oldGeohash,
                eventType: 'driver_left'
            ));

            // Add to new geohash
            Redis::sadd("geohash:drivers:{$newGeohash}", $driver->id);

            // Broadcast driver_entered
            broadcast(new DriverLocationUpdated(
                driverId: $driver->id,
                lat: $lat,
                lng: $lng,
                geohash: $newGeohash,
                eventType: 'driver_entered',
                bearing: $data['bearing'] ?? null,
                speed: $data['speed'] ?? null
            ));

        } else {

            /*
            |--------------------------------------------------------------------------
            | 3) Driver moved inside SAME geohash → moved
            |--------------------------------------------------------------------------
            */
            Redis::sadd("geohash:drivers:{$newGeohash}", $driver->id);

            broadcast(new DriverLocationUpdated(
                driverId: $driver->id,
                lat: $lat,
                lng: $lng,
                geohash: $newGeohash,
                eventType: 'driver_moved',
                bearing: $data['bearing'] ?? null,
                speed: $data['speed'] ?? null
            ));
        }

        /*
        |--------------------------------------------------------------------------
        | 4) Save driver location + TTL
        |--------------------------------------------------------------------------
        */
        Redis::hmset("driver:{$driver->id}:location", [
            'lat'        => $lat,
            'lng'        => $lng,
            'geohash'    => $newGeohash,
            'updated_at' => now()->toIso8601String(),
        ]);

        Redis::expire("driver:{$driver->id}:location", 20);

        return response()->json(['status' => true]);
    }
}
