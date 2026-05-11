<?php

namespace App\Services;

class TrafficPricingService
{
    public function calculateForRoute(array $routeSnapshot, float $baseAmount): array
    {
        $delaySeconds = (int) ($routeSnapshot['traffic_delay_seconds'] ?? 0);
        $durationSeconds = (int) ($routeSnapshot['duration_seconds'] ?? 0);
        $baselineSeconds = max(1, $durationSeconds - $delaySeconds);

        $multiplier = $this->resolveMultiplier($delaySeconds, $baselineSeconds);
        $surgedPrice = round($baseAmount * $multiplier, 2);
        $surgeAmount = round($surgedPrice - $baseAmount, 2);

        return [
            'multiplier' => $multiplier,
            'delay_seconds' => $delaySeconds,
            'delay_minutes' => round($delaySeconds / 60, 2),
            'surge_amount' => $surgeAmount,
            'surged_price' => $surgedPrice,
        ];
    }

    private function resolveMultiplier(int $delaySeconds, int $baselineSeconds): float
    {
        if ($delaySeconds <= 0) {
            return 1.0;
        }

        $ratio = $delaySeconds / max(1, $baselineSeconds);

        if ($ratio <= 0.10) {
            return 1.05;
        }
        if ($ratio <= 0.25) {
            return 1.10;
        }
        if ($ratio <= 0.50) {
            return 1.20;
        }
        if ($ratio <= 0.75) {
            return 1.35;
        }

        return 1.50;
    }
}