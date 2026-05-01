<?php

namespace App\Repositories;

use App\Models\Trip;
use App\Models\TripType;
use App\Models\TripWaypoint;
use App\Models\Driver;
use App\Models\Offer;
use App\Models\Coupon;
use App\Services\WalletService;
use App\Services\Payments\PaymentGatewayInterface;
use App\Services\Payments\PaymentGatewayFactoryInterface;
use App\Services\NotificationService;
use App\Jobs\NewTripRetryJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use App\Support\GeoHash;
use App\Events\NewTripRequest;
use App\Events\TripAccepted;
use App\Events\TripLocked;
use Illuminate\Support\Facades\Log;

class TripRepository
{
    public function __construct(
        protected WalletService $walletService,
        protected PaymentGatewayFactoryInterface $paymentGatewayFactory,
        protected NotificationService $notificationService
    ) {}

    public function calculateTripDistance(array $data): float
    {
        $points = [];
        $points[] = ['lat' => $data['origin_lat'], 'lng' => $data['origin_lng']];
        if (!empty($data['waypoints'])) {
            foreach ($data['waypoints'] as $wp) {
                $points[] = ['lat' => $wp['lat'], 'lng' => $wp['lng']];
            }
        }
        $points[] = ['lat' => $data['destination_lat'], 'lng' => $data['destination_lng']];

        $totalKm = 0;
        for ($i = 0; $i < count($points) - 1; $i++) {
            $totalKm += GeoHash::distanceKm(
                $points[$i]['lat'], $points[$i]['lng'],
                $points[$i + 1]['lat'], $points[$i + 1]['lng']
            );
        }

        return round($totalKm, 2);
    }

    public function createTrip($user, array $data): Trip
    {
        return DB::transaction(function () use ($user, $data) {
            $tripType = TripType::findOrFail($data['trip_type_id']);
            $distanceKm = $this->calculateTripDistance($data);

            $baseFare    = $tripType->base_fare;
            $pricePerKm  = $tripType->price_per_km;
            $original    = $baseFare + ($distanceKm * $pricePerKm);

            $discountAmount = 0;
            $offerId = null;
            $couponId = null;

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

            $finalPrice = max(0, $original - $discountAmount);

            // compute billing before creating trip to avoid an extra update
            $profitMargin = $tripType->profit_margin ?? 0;
            $driverShare = $finalPrice - ($finalPrice * ($profitMargin / 100));
            $promoDifference = max(0, $original - $finalPrice);
            $driverCreditAmount = max(0, $driverShare + $promoDifference);

            $billing = [
                'original_price' => $original,
                'final_price' => $finalPrice,
                'profit_margin' => $profitMargin,
                'driver_share' => round($driverShare, 2),
                'promo_difference' => round($promoDifference, 2),
                'driver_credit_amount' => round($driverCreditAmount, 2),
                'offer_id' => $offerId,
                'coupon_id' => $couponId,
            ];

            $trip = Trip::create([
                'client_id'      => $user->id,
                'trip_type_id'   => $tripType->id,
                'status'         => 'searching_driver',
                'payment_method' => $data['payment_method'],
                'distance_km'    => $distanceKm,
                'base_fare'      => $baseFare,
                'price_per_km'   => $pricePerKm,
                'original_price' => $original,
                'discount_amount' => $discountAmount,
                'final_price'    => $finalPrice,
                'offer_id'       => $offerId,
                'coupon_id'      => $couponId,
                'billing_breakdown' => $billing,
                'driver_credit_amount' => $driverCreditAmount,
                'is_paid' => false,
                'negotiation_enabled' => $data['negotiation_enabled'] ?? false,
                'origin_lat'     => $data['origin_lat'],
                'origin_lng'     => $data['origin_lng'],
                'origin_address' => $data['origin_address'] ?? null,
                'destination_lat' => $data['destination_lat'],
                'destination_lng' => $data['destination_lng'],
                'destination_address' => $data['destination_address'] ?? null,
                'reminder' => $data['reminder'] ?? 0,
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
            $originGeohash = GeoHash::encode($trip->origin_lat, $trip->origin_lng, 7);
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
                $drivers = Driver::whereIn('id', $nearbyDrivers)->where('is_online', 1)->whereHas('vehicle', function ($query) use ($trip) {
                    $query->where('is_active', 1);
                    $query->where('trip_type_id', $trip->trip_type_id);
                })->get();
                foreach ($drivers as $driver) {
                    broadcast(new NewTripRequest($trip, $driver->id));
                    $this->notificationService->notifyNewTripRequest($trip, $driver);
                }
            }

            NewTripRetryJob::dispatch($trip->id, 0)->delay(now()->addMinutes(5));

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

            if ($driver->is_online !== 1) {
                return ['status' => false, 'message' => 'Driver is offline'];
            }

            $activeTrip = Trip::where('driver_id', $driver->id)
                ->whereIn('status', ['driver_assigned', 'driver_arrived', 'in_progress'])
                ->first();

            if ($activeTrip) {
                return ['status' => false, 'message' => 'Driver already has an active trip'];
            }

            $trip->update(['driver_id' => $driver->id, 'status' => 'driver_assigned', 'driver_assigned_at' => now()]);

            // Try to collect payment at accept
            $billing = $trip->billing_breakdown ?? [];
            $profitMargin = $trip->tripType?->profit_margin ?? 0;
            $driverShare = $trip->final_price - ($trip->final_price * ($profitMargin / 100));
            $promoDifference = max(0, $trip->original_price - $trip->final_price);
            $driverCreditAmount = max(0, $driverShare + $promoDifference);

            if (! $trip->is_paid) {
                if ($trip->payment_method === 'wallet') {
                    $available = $this->walletService->getBalance($trip->client);

                    if ($available >= $trip->final_price) {
                        $this->walletService->decrement($trip->client, $trip->final_price);
                        $billing = array_merge($billing, ['wallet_charged' => $trip->final_price]);
                        $trip->update(['is_paid' => true, 'paid_at' => now(), 'driver_credit_amount' => $driverCreditAmount, 'billing_breakdown' => $billing]);
                    } elseif ($available > 0) {
                        $this->walletService->decrement($trip->client, $available);
                        $remaining = $trip->final_price - $available;
                        $billing = array_merge($billing, ['wallet_charged' => $available, 'cash_due' => $remaining]);
                        $trip->update(['payment_method' => 'cash', 'reminder' => $remaining, 'billing_breakdown' => $billing]);
                    } else {
                        $billing = array_merge($billing, ['wallet_charged' => 0, 'cash_due' => $trip->final_price]);
                        $trip->update(['payment_method' => 'cash', 'reminder' => $trip->final_price, 'billing_breakdown' => $billing]);
                    }
                } elseif ($trip->payment_method === 'visa') {
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
                        $trip->update(['is_paid' => true, 'paid_at' => now(), 'driver_credit_amount' => $driverCreditAmount, 'billing_breakdown' => $billing]);
                    } else {
                        $billing['baymob_failed'] = $res['raw'] ?? $res;
                        $trip->update(['payment_method' => 'cash', 'reminder' => $trip->final_price, 'billing_breakdown' => $billing]);
                    }
                }
            }

            // Credit driver if paid
            if ($trip->is_paid && ! $trip->driver_credited) {
                $driverWallet = $driver->wallet()->first() ?: $driver->wallet()->create(['balance' => 0]);
                $credit = $trip->driver_credit_amount ?? ($billing['driver_credit_amount'] ?? $driverCreditAmount);
                if ($credit > 0) {
                    $driverWallet->increment('balance', $credit);
                    $trip->update(['driver_credited' => true]);
                }
            }

            // Broadcast + notify
            broadcast(new TripAccepted($trip))->toOthers();
            broadcast(new TripLocked($trip->id))->toOthers();
            $trip->load('client');
            $this->notificationService->notifyTripAccepted($trip);

            return ['status' => true, 'message' => 'Trip accepted successfully', 'trip' => $trip, 'trip_channel' => "trip.{$trip->id}"];
        });
    }

    public function clientCancel(Trip $trip, $client, $reason = null, $description = null): array
    {
        // Decide if cancellation before start or after
        $beforeStart = in_array($trip->status, ['searching_driver', 'driver_assigned', 'driver_arrived']);

        $trip->update(['status' => 'cancelled_by_client', 'cancelled_at' => now(), 'cancelled_by' => 'client', 'cancel_reason' => $reason, 'cancel_description' => $description]);

        if ($beforeStart) {
            try {
                $billing = $trip->billing_breakdown ?? [];
                if ($trip->driver_credited && $trip->driver) {
                    $deduct = $trip->driver_credit_amount ?? ($billing['driver_credit_amount'] ?? 0);
                    $driverWallet = $trip->driver->wallet()->first();
                    if ($driverWallet && $driverWallet->balance >= $deduct && $deduct > 0) {
                        $driverWallet->decrement('balance', $deduct);
                    }
                    $trip->update(['driver_credited' => false]);
                }

                if (! empty($billing['wallet_charged']) && $billing['wallet_charged'] > 0) {
                    if ($trip->client && $trip->client->wallet) {
                        $trip->client->wallet->increment('balance', $billing['wallet_charged']);
                    }
                } elseif (! empty($billing['baymob_transaction_id'])) {
                    $gateway = $this->paymentGatewayFactory->get('visa');
                    if ($gateway) {
                        $gateway->refund($billing['baymob_transaction_id'], $billing['baymob_charged_amount'] ?? $trip->final_price);
                    } else {
                        Log::error('No payment gateway available to process refund: ' . ($billing['baymob_transaction_id'] ?? '')); 
                    }
                } elseif ($trip->is_paid) {
                    if ($trip->client && $trip->client->wallet) {
                        $trip->client->wallet->increment('balance', $trip->final_price);
                    }
                }

                $trip->update(['is_paid' => false, 'paid_at' => null]);
            } catch (\Exception $e) {
                Log::error('Failed to refund client on cancel: ' . $e->getMessage());
            }

            // Notify
            broadcast(new \App\Events\TripCancelled($trip))->toOthers();
            $this->notificationService->notifyTripCancelled($trip, 'client');

            try { $client->increment('trips_cancelled_count'); } catch (\Exception $e) { Log::error($e->getMessage()); }
        } else {
            // After start: charge base fare as cancellation fee
            try {
                $fee = (float) ($trip->base_fare ?? 0);
                if ($trip->client && $trip->client->wallet && $trip->client->wallet->balance >= $fee) {
                    $trip->client->wallet->decrement('balance', $fee);
                } elseif ($trip->payment_method === 'visa') {
                    try {
                        $gateway = $this->paymentGatewayFactory->get('visa');
                        if ($gateway) {
                            $gateway->charge(['amount' => $fee, 'currency' => 'Egp', 'description' => 'Cancellation fee', 'customer' => ['id' => $trip->client->id]]);
                        } else {
                            Log::error('No payment gateway available to charge cancellation fee');
                        }
                    } catch (\Exception $e) { Log::error($e->getMessage()); }
                } else {
                    $trip->increment('reminder', $fee);
                }

                if ($trip->driver) {
                    $driverWallet = $trip->driver->wallet()->first() ?: $trip->driver->wallet()->create(['balance' => 0]);
                    if ($fee > 0) $driverWallet->increment('balance', $fee);
                }
            } catch (\Exception $e) {
                Log::error('Failed to apply cancellation billing: ' . $e->getMessage());
            }

            broadcast(new \App\Events\TripCancelled($trip))->toOthers();
            $this->notificationService->notifyTripCancelled($trip, 'client');
        }

        return ['status' => true, 'message' => 'Trip cancelled successfully', 'trip' => $trip];
    }

    public function driverCancel(Trip $trip, $driver, $reason = null, $description = null): array
    {
        $trip->update(['status' => 'cancelled_by_driver', 'cancelled_at' => now(), 'cancelled_by' => 'driver', 'cancel_reason' => $reason, 'cancel_description' => $description]);

        try {
            $billing = $trip->billing_breakdown ?? [];

            if ($trip->driver_credited && $trip->driver) {
                $deduct = $trip->driver_credit_amount ?? ($billing['driver_credit_amount'] ?? 0);
                $driverWallet = $trip->driver->wallet()->first();
                if ($driverWallet && $driverWallet->balance >= $deduct && $deduct > 0) {
                    $driverWallet->decrement('balance', $deduct);
                }
                $trip->update(['driver_credited' => false]);
            }

            if (! empty($billing['wallet_charged']) && $billing['wallet_charged'] > 0) {
                if ($trip->client && $trip->client->wallet) {
                    $trip->client->wallet->increment('balance', $billing['wallet_charged']);
                }
            } elseif (! empty($billing['baymob_transaction_id'])) {
                    $gateway = $this->paymentGatewayFactory->get('visa');
                    if ($gateway) {
                        $gateway->refund($billing['baymob_transaction_id'], $billing['baymob_charged_amount'] ?? $trip->final_price);
                    } else {
                        Log::error('No payment gateway available to process refund: ' . ($billing['baymob_transaction_id'] ?? ''));
                    }
            } elseif ($trip->is_paid) {
                if ($trip->client && $trip->client->wallet) {
                    $trip->client->wallet->increment('balance', $trip->final_price);
                }
            }

            $trip->update(['is_paid' => false, 'paid_at' => null]);
        } catch (\Exception $e) {
            Log::error('Failed to refund client on driver cancel: ' . $e->getMessage());
        }

        broadcast(new \App\Events\TripCancelled($trip))->toOthers();
        $this->notificationService->notifyTripCancelled($trip, 'driver');

        try { $driver->increment('trips_cancelled_count'); } catch (\Exception $e) { Log::error($e->getMessage()); }

        return ['status' => true, 'message' => 'Trip cancelled successfully', 'trip_id' => $trip->id];
    }
}
