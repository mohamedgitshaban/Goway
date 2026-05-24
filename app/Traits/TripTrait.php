<?php

namespace App\Traits;

use App\Events\NewTripRequest;
use App\Models\Driver;
use App\Models\Trip;
use App\Services\DriverDestinationPreferenceService;
use App\Support\GeoHash;
use Illuminate\Support\Facades\Redis;

trait TripTrait
{
    public function billing($route, $pricing, $discountData, $profitMargin, $driverShare, $driverCreditAmount)
    {
        return [
            'route_source' => $route['source'] ?? 'unknown',
            'distance_meters' => (int) ($route['distance_meters'] ?? round($pricing['distance_km'] * 1000)),
            'distance_km' => $pricing['distance_km'],
            'estimated_duration_minutes' => (float) ($route['duration_minutes'] ?? 0),
            'estimated_duration_seconds' => (int) ($route['duration_seconds'] ?? 0),
            'static_duration_seconds' => (int) ($route['static_duration_seconds'] ?? 0),
            'traffic_delay_minutes' => (float) ($route['traffic_delay_minutes'] ?? 0),
            'traffic_delay_seconds' => (int) ($route['traffic_delay_seconds'] ?? 0),
            'has_traffic' => (bool) ($route['has_traffic'] ?? false),
            'has_tolls' => (bool) ($route['has_tolls'] ?? false),
            'toll_amount' => (float) ($pricing['toll_amount'] ?? 0),
            'toll_currency' => $route['toll_currency'] ?? null,
            'distance_amount' => (float) ($pricing['distance_amount'] ?? 0),
            'subtotal_before_adjustments' => (float) ($pricing['subtotal_before_adjustments'] ?? 0),
            'subtotal_with_tolls' => (float) ($pricing['subtotal_with_tolls'] ?? 0),
            'original_price' => $pricing['original_price'],
            'final_price' => max(0, $pricing['original_price'] - $discountData['discount_amount']),
            'surge_multiplier' => (float) ($pricing['demand_surge_multiplier'] ?? 1.0),
            'surge_amount' => (float) ($pricing['demand_surge_amount'] ?? 0),
            'traffic_surge_multiplier' => (float) ($pricing['traffic_surge_multiplier'] ?? 1.0),
            'traffic_surge_amount' => (float) ($pricing['traffic_surge_amount'] ?? 0),
            'profit_margin' => $profitMargin,
            'driver_share' => round($driverShare, 2),
            'driver_credit_amount' => round($driverCreditAmount, 2),
            'offer_id' => $discountData['offer_id'],
            'coupon_id' => $discountData['coupon_id'],
            'client_burn_wallet_amount' => 0,
            'client_indebtedness' => 0,
        ];
    }
    public function TripRequestFormate(Trip $trip, $type = 'new_trip_request')
    {
        $originGeohash = GeoHash::encode($trip->origin_lat, $trip->origin_lng, 5);
        $cells = array_merge([$originGeohash], GeoHash::neighbors($originGeohash));
        $nearbyDrivers = [];
        foreach ($cells as $cell) {
            $members = Redis::smembers("geohash:drivers:{$cell}");
            if (! empty($members)) {
                foreach ($members as $m) {
                    $nearbyDrivers[] = $m;
                }
            }
        }
        $nearbyDrivers = array_values(array_unique($nearbyDrivers));

        if (! empty($nearbyDrivers)) {
            $drivers = Driver::with('destinationPreference')->whereIn('id', $nearbyDrivers)->where('is_online', 1)->where('is_idle', 1)->whereHas('activeVehicle', function ($query) use ($trip) {
                $query->where('trip_type_id', $trip->trip_type_id);
            })->get();

            $filteredDrivers = [];
            foreach ($drivers as $driver) {
                if (app(DriverDestinationPreferenceService::class)->matchesTrip($driver, $trip)) {
                    $filteredDrivers[] = $driver;
                }
            }

            foreach ($filteredDrivers as $driver) {
                broadcast(new NewTripRequest($trip, $driver->id, $type));
                if ($type === 'new_trip_request') {
                    $this->notificationService->notifyNewTripRequest($trip, $driver);
                }
            }
        }
    }
    private function registerTripDemand(Trip $trip): void
    {
        if ($trip->status !== 'searching_driver') {
            return;
        }

        $geohash = GeoHash::encode((float) $trip->origin_lat, (float) $trip->origin_lng, 5);
        Redis::sadd($this->surgePricing->demandKey($geohash), $trip->id);
        Redis::sadd($this->surgePricing->tripTypeDemandKey($geohash, (int) $trip->trip_type_id), $trip->id);
    }

    private function unregisterTripDemand(Trip $trip): void
    {
        $geohash = GeoHash::encode((float) $trip->origin_lat, (float) $trip->origin_lng, 5);
        Redis::srem($this->surgePricing->demandKey($geohash), $trip->id);
        Redis::srem($this->surgePricing->tripTypeDemandKey($geohash, (int) $trip->trip_type_id), $trip->id);
    }
    public function collectBilling(Trip $trip): void
    {
        // Try to collect payment at accept
        $billing = $trip->billing_breakdown ?? [];

        switch ($trip->payment_method) {
            case 'wallet':
                $available = $this->walletService->getBalance($trip->client);
                if ($available >= $trip->final_price) {
                    $billing['client_burn_wallet_amount'] = $trip->final_price;

                    $trip->update([
                        'driver_credit_amount' => $trip->original_price,
                        'billing_breakdown' => $billing,
                    ]);
                } else {
                    $billing['client_burn_wallet_amount'] = $available;
                    $trip->update([
                        'driver_credit_amount' => $trip->original_price - ($trip->final_price - $available),
                        'billing_breakdown' => $billing,
                        'reminder' => $trip->final_price - $available,
                    ]);
                }
                break;
            case 'visa':
                $chargePayload = [
                    'amount' => $trip->final_price,
                    'currency' => 'Egp',
                    'description' => 'Goway trip payment',
                    'customer' => [
                        'id' => $trip->client->id,
                        'name' => $trip->client->name ?? ($trip->client->first_name . ' ' . $trip->client->last_name),
                        'phone' => $trip->client->phone,
                    ],
                ];

                $gateway = $this->paymentGatewayFactory->get('visa');
                if ($gateway) {
                    $res = $gateway->charge($chargePayload);
                } else {
                    $res = ['success' => false, 'raw' => 'no_payment_gateway_available'];
                }
                if (!empty($res['success']) && $res['success'] === true) {
                    $billing['baymob_transaction_id'] = $res['transaction_id'] ?? null;
                    $billing['baymob_charged_amount'] = $trip->final_price;
                    $trip->update(['is_paid' => true, 'paid_at' => now(), 'driver_credit_amount' => $trip->original_price, 'billing_breakdown' => $billing]);
                } else {
                    $billing['baymob_failed'] = $res['raw'] ?? $res;
                    $trip->update([
                        'payment_method' => 'cash',
                        'reminder' => $trip->final_price,
                        'driver_credit_amount' => $trip->original_price - $trip->final_price,
                        'billing_breakdown' => $billing
                    ]);
                }
                break;
            default:
                // For cash, we consider it paid at accept and will collect from driver at end of trip
                $trip->update([
                    'driver_credit_amount' => $trip->original_price - $trip->final_price, // the driver will put in his wallet is the original price - the final price because the discount amount is not come from driver so we will not consider it in driver credit amount
                    'reminder' => $trip->final_price,
                ]);
                break;
        }
        // If wallet balance is negative, add to trip reminder
        $negativeBalance = round(abs(min(0, $this->walletService->getBalance($trip->client))), 2);
        $existingIndebtedness = round((float) ($billing['client_indebtedness'] ?? 0), 2);

        if ($negativeBalance > 0 && $existingIndebtedness !== $negativeBalance) {
            $billing['client_indebtedness'] = $negativeBalance;
            $trip->reminder = round((float) $trip->reminder - $existingIndebtedness + $negativeBalance, 2);
            $trip->billing_breakdown = $billing;
            $trip->save();
        }
    }
    public function profitCalc($price, $profitMargin): array
    {
        $driverShare = ($price * ($profitMargin / 100));
        $driverCreditAmount = max(0, $price - $driverShare);
        return [
            'driver_share' => round($driverShare, 2),
            'driver_credit_amount' => round($driverCreditAmount, 2),
        ];
    }
    public function refundBilling(Trip $trip): void {}
}
