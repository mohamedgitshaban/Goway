<?php

namespace App\Support;

class GeoHash
{
    protected static $base32 = '0123456789bcdefghjkmnpqrstuvwxyz';

    public static function encode($lat, $lng, $precision = 7)
    {
        $latInterval = [-90.0, 90.0];
        $lngInterval = [-180.0, 180.0];

        $geohash = '';
        $isEven = true;
        $bit = 0;
        $ch = 0;

        $bits = [16, 8, 4, 2, 1];

        while (strlen($geohash) < $precision) {
            if ($isEven) {
                $mid = ($lngInterval[0] + $lngInterval[1]) / 2;
                if ($lng > $mid) {
                    $ch |= $bits[$bit];
                    $lngInterval[0] = $mid;
                } else {
                    $lngInterval[1] = $mid;
                }
            } else {
                $mid = ($latInterval[0] + $latInterval[1]) / 2;
                if ($lat > $mid) {
                    $ch |= $bits[$bit];
                    $latInterval[0] = $mid;
                } else {
                    $latInterval[1] = $mid;
                }
            }

            $isEven = !$isEven;

            if ($bit < 4) {
                $bit++;
            } else {
                $geohash .= self::$base32[$ch];
                $bit = 0;
                $ch = 0;
            }
        }

        return $geohash;
    }

    public static function neighbors($hash)
    {
        $neighbors = [];

        $neighbors['top']    = self::adjacent($hash, 'top');
        $neighbors['bottom'] = self::adjacent($hash, 'bottom');
        $neighbors['right']  = self::adjacent($hash, 'right');
        $neighbors['left']   = self::adjacent($hash, 'left');

        $neighbors['topleft']     = self::adjacent($neighbors['left'], 'top');
        $neighbors['topright']    = self::adjacent($neighbors['right'], 'top');
        $neighbors['bottomleft']  = self::adjacent($neighbors['left'], 'bottom');
        $neighbors['bottomright'] = self::adjacent($neighbors['right'], 'bottom');

        return $neighbors;
    }

    protected static function adjacent($hash, $direction)
    {
        $neighbor = [
            'right'  => 'bc01fg45238967deuvhjyznpkmstqrwx',
            'left'   => '238967debc01fg45kmstqrwxuvhjyznp',
            'top'    => 'p0r21436x8zb9dcf5h7kjnmqesgutwvy',
            'bottom' => '14365h7k9dcfesgujnmqp0r2twvyx8zb',
        ];

        $border = [
            'right'  => 'bcfguvyz',
            'left'   => '0145hjnp',
            'top'    => 'prxz',
            'bottom' => '028b',
        ];

        $lastChar = substr($hash, -1);
        $type = strlen($hash) % 2 ? 'odd' : 'even';
        $base = substr($hash, 0, -1);

        if (strpos($border[$direction], $lastChar) !== false && $base !== '') {
            $base = self::adjacent($base, $direction);
        }

        return $base . self::$base32[strpos($neighbor[$direction], $lastChar)];
    }

    public static function distanceKm($lat1, $lng1, $lat2, $lng2)
    {
        $earthRadius = 6371;

        $lat1 = deg2rad($lat1);
        $lng1 = deg2rad($lng1);
        $lat2 = deg2rad($lat2);
        $lng2 = deg2rad($lng2);

        $dlat = $lat2 - $lat1;
        $dlng = $lng2 - $lng1;

        $a = sin($dlat/2) ** 2 + cos($lat1) * cos($lat2) * sin($dlng/2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));

        return $earthRadius * $c;
    }
}
