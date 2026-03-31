<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Models\TripWaypoint;
use App\Models\TripType;
use App\Models\Coupon;
use App\Models\Offer;
use App\Events\NewTripRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class ClientTripController extends Controller
{
    public function store(Request $request)
    {
        $user = $request->user(); // client

        $data = $request->validate([
            'trip_type_id'      => 'required|exists:trip_types,id',
            'payment_method'    => 'required|in:cash,wallet',
            'origin_lat'        => 'required|numeric',
            'origin_lng'        => 'required|numeric',
            'origin_address'    => 'nullable|string',
            'destination_lat'   => 'required|numeric',
            'destination_lng'   => 'required|numeric',
            'destination_address' => 'nullable|string',
            'waypoints'         => 'array',
            'waypoints.*.lat'   => 'required_with:waypoints|numeric',
            'waypoints.*.lng'   => 'required_with:waypoints|numeric',
            'waypoints.*.address' => 'nullable|string',
            'coupon_code'       => 'nullable|string',
            'negotiation_enabled' => 'boolean',
        ]);

        return DB::transaction(function () use ($data, $user) {

            $tripType = TripType::findOrFail($data['trip_type_id']);

            // TODO: حساب المسافة الفعلية
            $distanceKm = 10.0;

            $baseFare    = $tripType->base_fare;
            $pricePerKm  = $tripType->price_per_km;
            $original    = $baseFare + ($distanceKm * $pricePerKm);

            $discountAmount = 0;
            $offerId = null;
            $couponId = null;

            // Offer
            $offer = Offer::active()->where('trip_type_id', $tripType->id)->first();
            if ($offer) {
                $offerDiscount = $original * 0.2;
                $discountAmount += $offerDiscount;
                $offerId = $offer->id;
            }

            // Coupon
            if (!empty($data['coupon_code'])) {
                $coupon = Coupon::active()->where('code', $data['coupon_code'])->first();
                if ($coupon && $coupon->isValidFor($user, $tripType)) {
                    $couponDiscount = 10;
                    $discountAmount += $couponDiscount;
                    $couponId = $coupon->id;
                }
            }

            $finalPrice = max(0, $original - $discountAmount);

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
                'negotiation_enabled' => $data['negotiation_enabled'] ?? false,
                'origin_lat'     => $data['origin_lat'],
                'origin_lng'     => $data['origin_lng'],
                'origin_address' => $data['origin_address'] ?? null,
                'destination_lat' => $data['destination_lat'],
                'destination_lng' => $data['destination_lng'],
                'destination_address' => $data['destination_address'] ?? null,
            ]);

            // Save waypoints
            if (!empty($data['waypoints'])) {
                foreach ($data['waypoints'] as $index => $wp) {
                    TripWaypoint::create([
                        'trip_id' => $trip->id,
                        'order'   => $index + 1,
                        'lat'     => $wp['lat'],
                        'lng'     => $wp['lng'],
                        'address' => $wp['address'] ?? null,
                    ]);
                }
            }

            /*
            |--------------------------------------------------------------------------
            | 🔥 إرسال الرحلة للسائقين القريبين (geohash)
            |--------------------------------------------------------------------------
            */
            $originGeohash = $this->calculateGeohash($trip->origin_lat, $trip->origin_lng);

            $nearbyDrivers = Redis::smembers("geohash:drivers:{$originGeohash}");

            foreach ($nearbyDrivers as $driverId) {
                broadcast(new NewTripRequest($trip, $driverId));
            }

            return response()->json([
                'status'  => true,
                'message' => 'Trip created successfully',
                'trip_id' => $trip->id,
                'trip_channel' => "trip.{$trip->id}",
            ]);
        });
    }
    public function estimate(Request $request)
    {
        $data = $request->validate([
            'origin_lat'        => 'required|numeric',
            'origin_lng'        => 'required|numeric',
            'destination_lat'   => 'required|numeric',
            'destination_lng'   => 'required|numeric',
            'waypoints'         => 'array',
            'waypoints.*.lat'   => 'required_with:waypoints|numeric',
            'waypoints.*.lng'   => 'required_with:waypoints|numeric',
        ]);

        // TODO: احسب المسافة الفعلية
        $distanceKm = 10.0;

        $tripTypes = \App\Models\TripType::all();

        $estimates = [];

        foreach ($tripTypes as $type) {

            $baseFare   = $type->base_fare;
            $pricePerKm = $type->price_per_km;

            $total = $baseFare + ($distanceKm * $pricePerKm);

            $estimates[] = [
                'trip_type_id' => $type->id,
                'name'         => $type->name_en,
                'image'        => $type->image ?? null,
                'distance_km'  => $distanceKm,
                'base_fare'    => $baseFare,
                'price_per_km' => $pricePerKm,
                'total'        => $total,
            ];
        }

        return response()->json([
            'status'    => true,
            'estimates' => $estimates,
        ]);
    }


    private function calculateGeohash($lat, $lng)
    {
        return substr(md5($lat . $lng), 0, 6); // placeholder
    }
}
