<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\GeoHash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class ClientNearbyDriversController extends Controller
{
    private const DRIVER_LOCATION_GEOHASH_PRECISION = 5;

    public function index(Request $request)
    {
        $data = $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
        ]);

        // geohash الخاص بالعميل
        $geohash = GeoHash::encode($data['lat'], $data['lng'], self::DRIVER_LOCATION_GEOHASH_PRECISION);

        // هات IDs السائقين في نفس geohash
        $driverIds = Redis::smembers("geohash:drivers:{$geohash}");

        // هات بيانات السائقين من DB
        $drivers = User::whereIn('id', $driverIds)
            ->where('usertype', 'driver')
            ->where('is_online', 1)
            ->where('is_idle', 1)
            ->get()
            ->map(function ($driver) {
                $loc = Redis::hgetall("driver:{$driver->id}:location");

                return [
                    'id'   => $driver->id,
                    'name' => $driver->name,
                    'lat'  => isset($loc['lat']) ? (float) $loc['lat'] : null,
                    'lng'  => isset($loc['lng']) ? (float) $loc['lng'] : null,
                ];
            })
            ->filter(fn ($d) => $d['lat'] !== null)
            ->values();

        return response()->json([
            'status'  => true,
            'geohash' => $geohash,
            'channel' => "nearby.drivers.{$geohash}",
            'drivers' => $drivers,
        ]);
    }
}
