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

        if (! $driver->isDriver()) {
            return response()->json(['status' => false, 'message' => 'Not a driver'], 403);
        }

        $data = $request->validate([
            'lat'     => 'required|numeric',
            'lng'     => 'required|numeric',
            'bearing' => 'nullable|numeric',
            'speed'   => 'nullable|numeric',
        ]);

        $lat = (float) $data['lat'];
        $lng = (float) $data['lng'];

        // geohash الجديد
        $newGeohash = GeoHash::encode($lat, $lng, 7);

        // geohash القديم من Redis
        $oldGeohash = Redis::hget("driver:{$driver->id}:location", 'geohash');

        /*
        |--------------------------------------------------------------------------
        | 1) Driver moved to a NEW geohash → left + entered
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
            | 2) Driver moved inside SAME geohash → moved
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
        | 3) Save driver location + TTL
        |--------------------------------------------------------------------------
        */
        Redis::hmset("driver:{$driver->id}:location", [
            'lat'        => $lat,
            'lng'        => $lng,
            'geohash'    => $newGeohash,
            'updated_at' => now()->toIso8601String(),
        ]);

        Redis::expire("driver:{$driver->id}:location", 20); // auto-remove after 20 sec

        return response()->json(['status' => true]);
    }
}
