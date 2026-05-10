<?php

namespace App\Services;

use App\Support\GeoHash;
use Illuminate\Support\Facades\Redis;

class SurgePricingService
{
    private const GEOHASH_PRECISION = 5;

    public function calculateForPoint(float $lat, float $lng, ?int $tripTypeId, float $baseAmount): array
    {
        $geohash = GeoHash::encode($lat, $lng, self::GEOHASH_PRECISION);
        $stats = $this->buildCellStats($geohash, $tripTypeId);

        $multiplier = $stats['multiplier'];
        $surgedPrice = round($baseAmount * $multiplier, 2);
        $surgeAmount = round($surgedPrice - $baseAmount, 2);

        return [
            'geohash' => $geohash,
            'multiplier' => $multiplier,
            'demand' => $stats['demand'],
            'supply' => $stats['supply'],
            'ratio' => $stats['ratio'],
            'surge_amount' => $surgeAmount,
            'surged_price' => $surgedPrice,
        ];
    }

    public function getMapMultipliers(float $lat, float $lng, ?int $tripTypeId): array
    {
        $center = GeoHash::encode($lat, $lng, self::GEOHASH_PRECISION);
        $cells = array_values(array_unique(array_merge([$center], GeoHash::neighbors($center))));

        $areas = [];
        foreach ($cells as $cell) {
            $stats = $this->buildCellStats($cell, $tripTypeId);
            $areas[] = [
                'geohash' => $cell,
                'demand' => $stats['demand'],
                'supply' => $stats['supply'],
                'ratio' => $stats['ratio'],
                'multiplier' => $stats['multiplier'],
                'label' => 'x' . number_format($stats['multiplier'], 1),
            ];
        }

        return [
            'center_geohash' => $center,
            'trip_type_id' => $tripTypeId,
            'areas' => $areas,
        ];
    }

    private function buildCellStats(string $geohash, ?int $tripTypeId): array
    {
        $demand = $this->getDemandCount($geohash, $tripTypeId);
        $supply = (int) Redis::scard("geohash:drivers:{$geohash}");

        $ratio = $supply > 0 ? round($demand / $supply, 2) : ($demand > 0 ? (float) $demand : 0.0);
        $multiplier = $this->resolveMultiplier($demand, $supply);

        return [
            'demand' => $demand,
            'supply' => $supply,
            'ratio' => $ratio,
            'multiplier' => $multiplier,
        ];
    }

    private function getDemandCount(string $geohash, ?int $tripTypeId): int
    {
        if ($tripTypeId) {
            return (int) Redis::scard($this->tripTypeDemandKey($geohash, $tripTypeId));
        }

        return (int) Redis::scard($this->demandKey($geohash));
    }

    private function resolveMultiplier(int $demand, int $supply): float
    {
        if ($demand <= 0) {
            return 1.0;
        }

        if ($supply <= 0) {
            return 2.0;
        }

        $ratio = $demand / $supply;

        if ($ratio <= 0.50) {
            return 1.0;
        }
        if ($ratio <= 1.00) {
            return 1.1;
        }
        if ($ratio <= 2.00) {
            return 1.2;
        }
        if ($ratio <= 3.00) {
            return 1.5;
        }
        if ($ratio <= 4.00) {
            return 1.8;
        }

        return 2.0;
    }

    public function demandKey(string $geohash): string
    {
        return "geohash:trip_requests:{$geohash}";
    }

    public function tripTypeDemandKey(string $geohash, int $tripTypeId): string
    {
        return "geohash:trip_requests:{$geohash}:trip_type:{$tripTypeId}";
    }
}
