<?php

namespace App\Support;

use Location\Coordinate;
use Location\Distance\Vincenty;
use Location\Geohash\Geohash as LibGeohash;

class GeoHash
{
    protected static ?LibGeohash $geohash = null;

    protected static function instance(): LibGeohash
    {
        if (! self::$geohash) {
            self::$geohash = new LibGeohash();
        }

        return self::$geohash;
    }

    public static function encode(float $lat, float $lng, int $precision = 7): string
    {
        return self::instance()->encode($lat, $lng, $precision);
    }

    public static function distanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $coord1 = new Coordinate($lat1, $lng1);
        $coord2 = new Coordinate($lat2, $lng2);

        $calculator = new Vincenty();

        return $calculator->getDistance($coord1, $coord2) / 1000; // متر → كم
    }
}
