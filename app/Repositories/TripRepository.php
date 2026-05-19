<?php

namespace App\Repositories;

use App\Models\Trip;
use App\Models\TripType;
use App\Models\TripWaypoint;
use App\Models\Driver;
use App\Models\Offer;
use App\Models\Coupon;
use App\Services\WalletService;
use App\Services\Payments\PaymentGatewayFactoryInterface;
use App\Services\NotificationService;
use App\Jobs\NewTripRetryJob;
use App\Services\GoogleRouteService;
use App\Services\SurgePricingService;
use App\Services\TrafficPricingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use App\Support\GeoHash;
use App\Events\NewTripRequest;
use App\Events\TripAccepted;
use Illuminate\Support\Facades\Log;
use App\Traits\TripTrait;

class TripRepository
{
    use TripTrait;
    public function __construct(
        protected WalletService $walletService,
        protected PaymentGatewayFactoryInterface $paymentGatewayFactory,
        protected NotificationService $notificationService,
        protected SurgePricingService $surgePricing,
        protected GoogleRouteService $googleRouteService,
        protected TrafficPricingService $trafficPricing
    ) {}

    public function calculateRouteSnapshot(array $data): array
    {
        return $this->googleRouteService->compute($data);
    }

    public function calculateTripDistance(array $data): float
    {
        return (float) ($this->calculateRouteSnapshot($data)['distance_km'] ?? 0.0);
    }
    public function buildTripQuote(TripType $tripType, array $data, ?array $routeSnapshot = null): array
    {
        $routeSnapshot ??= $this->calculateRouteSnapshot($data);

        $distanceKm = (float) ($routeSnapshot['distance_km'] ?? 0.0);
        $baseFare = (float) $tripType->base_fare;
        $pricePerKm = (float) $tripType->price_per_km;
        $distanceAmount = round($distanceKm * $pricePerKm, 2);
        $subtotalBeforeAdjustments = round($baseFare + $distanceAmount, 2);
        $tollAmount = (float) ($routeSnapshot['toll_amount'] ?? 0.0);
        $subtotalWithTolls = round($subtotalBeforeAdjustments + $tollAmount, 2);

        $demandSurge = $this->surgePricing->calculateForPoint(
            (float) $data['origin_lat'],
            (float) $data['origin_lng'],
            (int) $tripType->id,
            $subtotalWithTolls
        );

        $trafficSurge = $this->trafficPricing->calculateForRoute($routeSnapshot, (float) $demandSurge['surged_price']);

        return [
            'route' => $routeSnapshot,
            'pricing' => [
                'distance_km' => $distanceKm,
                'base_fare' => round($baseFare, 2),
                'price_per_km' => round($pricePerKm, 2),
                'distance_amount' => $distanceAmount,
                'subtotal_before_adjustments' => $subtotalBeforeAdjustments,
                'toll_amount' => round($tollAmount, 2),
                'subtotal_with_tolls' => $subtotalWithTolls,
                'demand_surge_multiplier' => (float) $demandSurge['multiplier'],
                'demand_surge_amount' => (float) $demandSurge['surge_amount'],
                'traffic_surge_multiplier' => (float) $trafficSurge['multiplier'],
                'traffic_surge_amount' => (float) $trafficSurge['surge_amount'],
                'original_price' => (float) $trafficSurge['surged_price'],
            ],
        ];
    }
    public function getDiscount(TripType $tripType, $user, array $data, float $original): array
    {
        $discountAmount = 0;
        $offer = Offer::active()->where('trip_type_id', $tripType->id)->first();
        if ($offer) {
            if ($offer->discount_type === 'percentage') {
                $offerDiscount = ($original * ($offer->discount_value / 100)) > $offer->max_discount ? $offer->max_discount : ($original * ($offer->discount_value / 100));
            } else {
                $offerDiscount = $offer->discount_value;
            }
            $discountAmount += $offerDiscount;
            $offerId = $offer->id;
        }

        if (!empty($data['coupon_code'])) {
            $coupon = Coupon::active()->where('code', $data['coupon_code'])->first();
            if ($coupon && $coupon->isValidFor($user, $tripType)) {
                if ($coupon->discount_type === 'percentage') {
                    $couponDiscount = ($original * ($coupon->discount_value / 100)) > $coupon->max_discount ? $coupon->max_discount : ($original * ($coupon->discount_value / 100));
                } else {
                    $couponDiscount = $coupon->discount_value;
                }
                $discountAmount += $couponDiscount;
                $couponId = $coupon->id;
            }
        }
        return ['discount_amount' => round($discountAmount, 2), 'offer_id' => $offerId ?? null, 'coupon_id' => $couponId ?? null];
    }
    public function createTrip($user, array $data): Trip
    {
        return DB::transaction(function () use ($user, $data) {

            $tripType = TripType::findOrFail($data['trip_type_id']);

            $quote = $this->buildTripQuote($tripType, $data);
            $route = $quote['route'];
            $pricing = $quote['pricing'];
            // get the total discount of trip based on offers and coupons, then compute final price
            $discountData = $this->getDiscount($tripType, $user, $data, $pricing['original_price']);
            $profitMargin = $tripType->profit_margin ?? 0;
            ['driver_share' => $driverShare, 'driver_credit_amount' => $driverCreditAmount] = $this->profitCalc($pricing['original_price'], $profitMargin);
            $trip = Trip::create([
                'client_id'      => $user->id,
                'trip_type_id'   => $tripType->id,
                'status'         => 'searching_driver',
                'payment_method' => $data['payment_method'],
                'distance_km'    => $pricing['distance_km'],
                'estimated_duration_minutes' => $route['duration_minutes'] ?? 0,
                'base_fare'      => $pricing['base_fare'],
                'price_per_km'   => $pricing['price_per_km'],
                'original_price' => $pricing['original_price'],
                'discount_amount' => $discountData['discount_amount'],
                'final_price'    => max(0, $pricing['original_price'] - $discountData['discount_amount']),
                'negotiated_price_before' => max(0, $pricing['original_price'] - $discountData['discount_amount']),
                'offer_id'       => $discountData['offer_id'],
                'coupon_id'      => $discountData['coupon_id'],
                'billing_breakdown' => $this->billing($route, $pricing, $discountData, $profitMargin, $driverShare, $driverCreditAmount),
                'driver_credit_amount' => 0, //the amount that put in driver wallet
                'driver_credit_deposed_amount' => $driverCreditAmount, //driver income
                'driver_share' => $driverShare, //driver share from trip price after discount and surge
                'is_paid' => false,
                'negotiation_enabled' => $data['negotiation_enabled'] ?? false,
                'origin_lat'     => $data['origin_lat'],
                'origin_lng'     => $data['origin_lng'],
                'origin_address' => $data['origin_address'] ?? null,
                'destination_lat' => $data['destination_lat'],
                'destination_lng' => $data['destination_lng'],
                'destination_address' => $data['destination_address'] ?? null,
                'reminder' => 0,
            ]);

            // Bulk insert waypoints to reduce DB calls
            if (! empty($data['waypoints'])) {
                $now = now();
                $rows = [];
                foreach ($data['waypoints'] as $index => $wp) {
                    $rows[] = [
                        'trip_id' => $trip->id,
                        'order'   => $index + 1,
                        'lat'     => $wp['lat'],
                        'lng'     => $wp['lng'],
                        'address' => $wp['address'] ?? null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                if (! empty($rows)) {
                    TripWaypoint::insert($rows);
                }
            }

            // Broadcast to nearby drivers
            // Broadcast to nearby drivers: collect members efficiently and fetch drivers in one query
            $this->TripRequestFormate($trip, 'new_trip_request');
            $this->registerTripDemand($trip);

            NewTripRetryJob::dispatch($trip->id, 0)->delay(now()->addMinutes(2));

            return $trip;
        });
    }

    public function assignDriver(Trip $trip, $driver): array
    {
        return DB::transaction(function () use ($trip, $driver) {
            // Basic guards
            if ($trip->status !== 'searching_driver') {
                return ['status' => false, 'message' => 'Trip already accepted by another driver'];
            }

            if ($driver->is_online != 1 || $driver->is_idle != 1) {
                return ['status' => false, 'message' => 'Driver is offline or not idle'];
            }
            $this->collectBilling($trip);
            $this->unregisterTripDemand($trip);
            $driver->update(['is_idle' => false]);
            $trip->update(['driver_id' => $driver->id, 'status' => 'driver_assigned', 'driver_assigned_at' => now()]);

            // Broadcast + notify
            broadcast(new TripAccepted($trip))->toOthers();
            $this->TripRequestFormate($trip, 'remove_trip_request');
            $trip->load('client');
            $this->notificationService->notifyTripAccepted($trip);

            return ['status' => true, 'message' => 'Trip accepted successfully', 'trip' => $trip, 'trip_channel' => "trip.{$trip->id}"];
        });
    }


    public function clientCancel(Trip $trip, $client, $reason = null, $description = null): array
    {
        $this->unregisterTripDemand($trip);
        // Decide if cancellation before start or after
        switch ($trip->status) {
            case 'searching_driver':
                $this->TripRequestFormate($trip, 'remove_trip_request');
                break;
            case 'driver_assigned':
            case 'driver_arrived':
                $trip->driver->update(['is_idle' => true]);
                $this->walletService->decrement($trip->client, $trip->base_fare, 'trip.trip_cancelled_by_client_fee', [
                    'trip_id' => $trip->id,
                ]);
                ['driver_share' => $driverShare, 'driver_credit_amount' => $driverCreditAmount] = $this->profitCalc($trip->base_fare, $trip->tripType->profit_margin);
                $this->walletService->increment($trip->driver, $driverCreditAmount, 'trip.trip_cancelled_by_client_fee', [
                    'trip_id' => $trip->id,
                ]);

                $trip->update([
                    'driver_credit_deposed_amount' => $driverCreditAmount,
                    'driver_credit_amount' => $driverCreditAmount,
                    'driver_share' => $driverShare,
                ]);
                break;
        }
        try {
            $client->increment('trips_cancelled_count');
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
        // Notify
        broadcast(new \App\Events\TripCancelled($trip))->toOthers();
        $this->notificationService->notifyTripCancelled($trip, 'client');
        $trip->update(['status' => 'cancelled_by_client', 'cancelled_at' => now(), 'cancelled_by' => 'client', 'cancel_reason' => $reason, 'cancel_description' => $description]);
        return ['status' => true, 'message' => 'Trip cancelled successfully', 'trip' => $trip];
    }
    public function driverCancel(Trip $trip, $driver, $reason = null, $description = null): array
    {
        $this->unregisterTripDemand($trip);


        $trip->driver()->update(['is_idle' => true]);

        switch ($trip->status) {
            case 'driver_assigned':
            case 'driver_arrived':
                // Apply cancellation fee only if assignment age is at least 5 minutes.
                if ($trip->started_at && $trip->started_at->lte(now()->subMinutes(5))) {

                    $this->walletService->decrement($trip->client, $trip->base_fare, 'trip.trip_cancelled_by_client_fee', [
                        'trip_id' => $trip->id,
                    ]);
                    ['driver_share' => $driverShare, 'driver_credit_amount' => $driverCreditAmount] = $this->profitCalc($trip->base_fare, $trip->tripType->profit_margin);
                    $this->walletService->increment($trip->driver, $driverCreditAmount, 'trip.trip_cancelled_by_client_fee', [
                        'trip_id' => $trip->id,
                    ]);

                    $trip->update([
                        'driver_credit_deposed_amount' => $driverCreditAmount,
                        'driver_credit_amount' => $driverCreditAmount,
                        'driver_share' => $driverShare,
                        'status' => 'cancelled_by_driver',
                        'cancelled_at' => now(),
                    ]);
                } else {
                    $trip->update([
                        'driver_id' => null,
                        'status' => 'searching_driver',
                        'driver_assigned_at' => null,
                        'final_price' => $trip->negotiated_price_before, // reset final price to original since driver cancelled before start
                        'original_price' => $trip->negotiated_price_before + $trip->discount_amount,
                        'driver_credit_amount' => 0,
                        'driver_credit_deposed_amount' => $trip->negotiated_price_before + $trip->discount_amount,
                        'driver_share' => ($trip->negotiated_price_before + $trip->discount_amount) * ($trip->tripType->profit_margin / 100),
                        // Keep billing values; collectBilling() is idempotent and should not run twice.
                    ]);
                    $this->TripRequestFormate($trip, 'new_trip_request');
                    $this->registerTripDemand($trip);
                }
                break;
            default:
                $trip->update(['status' => 'cancelled_by_driver', 'cancelled_at' => now(), 'cancelled_by' => 'driver', 'cancel_reason' => $reason, 'cancel_description' => $description]);
                $this->walletService->decrement($trip->driver, $trip->driver_share, 'trip.trip_cancelled_by_client_fee', [
                        'trip_id' => $trip->id,
                    ]);
                break;
        }

        broadcast(new \App\Events\TripCancelled($trip))->toOthers();
        $this->notificationService->notifyTripCancelled($trip, 'driver');

        try {
            $driver->increment('trips_cancelled_count');
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }

        return ['status' => true, 'message' => 'Trip cancelled successfully', 'trip_id' => $trip->id];
    }
    public function markTripAsPaid(Trip $trip, float $cost = 0)
    {
        $driver_increment_wallet = $trip->driver_credit_amount - $trip->driver_share  - $trip->reminder - $cost; // the amount that will put in driver wallet is the driver credit amount - the driver share - the cost that maybe the driver need to pay if the client give less than the final price
        if ($trip->driver && $driver_increment_wallet > 0) {
            $this->walletService->increment($trip->driver, $driver_increment_wallet, 'trip.complete_credit_driver', [
                'trip_id' => $trip->id,
            ]);
        } elseif ($trip->driver && $driver_increment_wallet < 0) {
            $this->walletService->decrement($trip->driver, abs($driver_increment_wallet), 'trip.complete_debit_driver', [
                'trip_id' => $trip->id,
            ]);
        }
        if ($trip->billing_breakdown['client_burn_wallet_amount'] ?? 0 > 0) {
            $this->walletService->decrement($trip->client, (float) ($trip->billing_breakdown['client_burn_wallet_amount'] ?? 0), 'trip.complete_burn_wallet_client', [
                'trip_id' => $trip->id,
            ]);
        }
        if ($cost > 0) {
            $this->walletService->increment($trip->client, $cost, 'trip.complete_credit_client', [
                'trip_id' => $trip->id,
            ]);
        }
        $trip->update([

            'paid_at' => now(),
            'status' => 'paid',
        ]);
    }
}
