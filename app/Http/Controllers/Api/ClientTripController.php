<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Models\TripWaypoint;
use App\Models\TripType;
use App\Models\Offer;
use App\Models\Coupon;
use App\Models\Driver;
use App\Models\Rating;
use Illuminate\Http\Request;
use App\Repositories\TripRepository;
use App\Http\Resources\TripResource;
use App\Services\NotificationService;
use App\Services\SurgePricingService;
use App\Support\GeoHash;
use Illuminate\Support\Facades\Redis;

class ClientTripController extends Controller
{
    public function __construct(
        protected TripRepository $trips,
        protected NotificationService $notificationService,
        protected SurgePricingService $surgePricing
    ) {}

    public function store(Request $request)
    {
        $user = $request->user(); // client

        $data = $request->validate([
            'trip_type_id'      => 'required|exists:trip_types,id',
            'payment_method'    => 'required|in:cash,wallet,visa',
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
        if (isset($data['payment_method']) && $data['payment_method'] === 'wallet') {
            $walletBalance = $user->wallet_balance ?? 0;
            if ($walletBalance <= 0) {
                return response()->json([
                    'status' => false,
                    'message' => 'Insufficient wallet balance',
                ], 400);
            }
        }
        $trip = $this->trips->createTrip($user, $data);

        return response()->json([
            'status'  => true,
            'message' => 'Trip created successfully',
            'trip_id' => $trip->id,
            'distance_km' => (float) $trip->distance_km,
            'estimated_duration_minutes' => (float) $trip->estimated_duration_minutes,
            'toll_amount' => (float) ($trip->billing_breakdown['toll_amount'] ?? 0),
            'traffic_delay_minutes' => (float) ($trip->billing_breakdown['traffic_delay_minutes'] ?? 0),
            'trip_channel' => "trip.{$trip->id}",
        ]);
    }

    public function index(Request $request)
    {
        $client = $request->user();

        if ($client->usertype !== 'client') {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        $limit = (int) $request->input('limit', 15);
        $search = $request->input('search');
        $status = $request->input('status');
        $tripTypeId = $request->input('trip_type_id');
        $from = $request->input('from');
        $to = $request->input('to');
        $sortBy = $request->input('sort_by', 'id');
        $sortDir = $request->input('sort_dir', 'desc');

        $query = Trip::with(['client', 'driver', 'tripType'])
            ->where('client_id', $client->id);

        if ($search) {
            $query->where(function ($q) use ($search) {
                if (is_numeric($search)) {
                    $q->where('id', $search);
                }

                $q->orWhereHas('driver', function ($qd) use ($search) {
                    $qd->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('phone', 'LIKE', "%{$search}%");
                });
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($tripTypeId) {
            $query->where('trip_type_id', $tripTypeId);
        }

        if ($from) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to) {
            $query->whereDate('created_at', '<=', $to);
        }

        $allowedSorts = ['id', 'created_at', 'started_at', 'completed_at', 'final_price', 'status'];
        if (! in_array($sortBy, $allowedSorts)) {
            $sortBy = 'id';
        }

        $sortDir = strtolower($sortDir) === 'asc' ? 'asc' : 'desc';

        $query->orderBy($sortBy, $sortDir);

        $data = $query->paginate($limit)->appends($request->query());

        return TripResource::collection($data);
    }
    public function applycoupon(Request $request)
    {
        $request->validate([
            'coupon_code' => 'required|string',
            'trip_type_id' => 'nullable|exists:trip_types,id',
        ]);

        $coupon = Coupon::active()->where('code', $request->coupon_code)->first();

        if (!$coupon) {
            return response()->json([
                'status' => false,
                'message' => __('messages.coupon_not_found_or_inactive'),
            ], 404);
        }

        $tripType = null;
        if ($request->trip_type_id) {
            $tripType = TripType::find($request->trip_type_id);
        }

        $isValid = $coupon->isValidFor($request->user(), $tripType);

        if (!$isValid) {
            return response()->json([
                'status' => false,
                'message' => __('messages.coupon_not_valid'),
            ], 400);
        }

        return response()->json([
            'status' => true,
            'message' => __('messages.coupon_valid'),
            'coupon' => $coupon,
        ]);
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

        $routeSnapshot = $this->trips->calculateRouteSnapshot($data);
        $distanceKm = (float) ($routeSnapshot['distance_km'] ?? 0.0);
        $tripTypes = \App\Models\TripType::where('status', 'active')->where('max_distance', '>=', $distanceKm)->with('activeOffer')->get();

        $estimates = [];

        foreach ($tripTypes as $type) {
            $quote = $this->trips->buildTripQuote($type, $data, $routeSnapshot);
            $pricing = $quote['pricing'];
            $totalWithSurge = (float) $pricing['original_price'];

            $estimates[] = [
                'trip_type_id' => $type->id,
                'name'         => $type->name_en,
                'image'        => $type->image ?? null,
                'distance_km'  => $distanceKm,
                'estimated_duration_minutes' => (float) ($routeSnapshot['duration_minutes'] ?? 0),
                'traffic_delay_minutes' => (float) ($routeSnapshot['traffic_delay_minutes'] ?? 0),
                'has_traffic' => (bool) ($routeSnapshot['has_traffic'] ?? false),
                'has_tolls' => (bool) ($routeSnapshot['has_tolls'] ?? false),
                'toll_amount' => (float) ($pricing['toll_amount'] ?? 0),
                'route_source' => $routeSnapshot['source'] ?? 'unknown',
                'base_fare'    => (float) $pricing['base_fare'],
                'price_per_km' => (float) $pricing['price_per_km'],
                'distance_amount' => (float) ($pricing['distance_amount'] ?? 0),
                'total_before_surge' => (float) ($pricing['subtotal_with_tolls'] ?? 0),
                'surge_multiplier' => (float) ($pricing['demand_surge_multiplier'] ?? 1.0),
                'surge_amount' => (float) ($pricing['demand_surge_amount'] ?? 0),
                'traffic_surge_multiplier' => (float) ($pricing['traffic_surge_multiplier'] ?? 1.0),
                'traffic_surge_amount' => (float) ($pricing['traffic_surge_amount'] ?? 0),
                'total' => $totalWithSurge,
                'after_offer'  => $type->activeOffer ? max(0, $type->activeOffer->discount_type === 'percentage' ? $totalWithSurge * (1 - $type->activeOffer->discount_value / 100) : $totalWithSurge - $type->activeOffer->discount_value) : $totalWithSurge,
                'offer'        => $type->activeOffer ? [
                    'id' => $type->activeOffer->id,
                    'title_ar' => $type->activeOffer->title_ar,
                    'title_en' => $type->activeOffer->title_en,
                    'image' => $type->activeOffer->image,
                    'discount_type' => $type->activeOffer->discount_type,
                    'discount_value' => $type->activeOffer->discount_value,
                    'max_discount_amount' => $type->activeOffer->max_discount_amount,
                ] : null,
            ];
        }

        return response()->json([
            'status'    => true,
            'estimates' => $estimates,
        ]);
    }

    public function cancel(Request $request, Trip $trip)
    {
        $client = $request->user();

        if ($trip->client_id !== $client->id) {
            return response()->json(['status' => false, 'message' => 'Not your trip'], 403);
        }

        if (! in_array($trip->status, ['searching_driver', 'driver_assigned', 'driver_arrived'])) {
            return response()->json(['status' => false, 'message' => 'Trip cannot be cancelled at this stage'], 400);
        }

        $reason = $request->cancel_reason ?? null;
        $desc = $request->cancel_description ?? null;

        $res = $this->trips->clientCancel($trip, $client, $reason, $desc);

        return response()->json($res);
    }

    public function acceptNegotiation(Request $request, Trip $trip)
    {
        $client = $request->user();

        $data = $request->validate([
            'negotiation_id' => 'required|exists:trip_negotiations,id',
        ]);

        if ($trip->client_id !== $client->id) {
            return response()->json(['status' => false, 'message' => 'Not your trip'], 403);
        }

        if ($trip->status !== 'searching_driver' && $trip->negotiation_status !== 'pending') {
            return response()->json(['status' => false, 'message' => 'Cannot accept offer at this stage'], 400);
        }

        $negotiation = \App\Models\TripNegotiation::where('id', $data['negotiation_id'])
            ->where('trip_id', $trip->id)
            ->firstOrFail();

        $negotiation->update(['status' => 'accepted']);
        // Reject all other pending negotiations
        \App\Models\TripNegotiation::where('trip_id', $trip->id)
            ->where('id', '!=', $negotiation->id)
            ->update(['status' => 'rejected']);
        ['driver_share' => $driverShare, 'driver_credit_amount' => $driverCreditAmount] = $this->profitCalc($negotiation->driver_proposed_price, $trip->tripType->profit_margin);
        $trip->update([
            'negotiated_price_before' => $trip->final_price,
            'negotiated_price_after' => $negotiation->proposed_price,
            'negotiation_price' => $negotiation->proposed_price,
            'final_price' => $negotiation->proposed_price,
            'original_price' => $negotiation->driver_proposed_price, // Store original price before negotiation for reference
            'driver_credit_deposed_amount' => $driverCreditAmount, //driver income
            'driver_share' => $driverShare, //driver share from trip price after discount and surge
            'negotiation_status' => 'accepted',
        ]);

        // use assignDriver from repository to handle wallet deduction, driver assigning, and events
        $driver = \App\Models\Driver::findOrFail($negotiation->driver_id);
        $res = $this->trips->assignDriver($trip, $driver);

        if (! $res['status']) {
            return response()->json($res, 400);
        }

        broadcast(new \App\Events\NegotiationAccepted($trip, $negotiation))->toOthers();

        // assignDriver already emits TripAccepted, so we don't necessarily need to reload/notify here unless explicitly wanted.
        $trip->load('driver');
        $this->notificationService->notifyNegotiationAccepted($trip, 'client');

        return response()->json([
            'status' => true,
            'message' => 'Offer accepted',
            'final_price' => $trip->final_price,
            'negotiation' => $negotiation,
        ]);
    }

    public function rejectNegotiation(Request $request, Trip $trip)
    {
        $client = $request->user();

        $data = $request->validate([
            'negotiation_id' => 'required|exists:trip_negotiations,id',
        ]);

        if ($trip->client_id !== $client->id) {
            return response()->json(['status' => false, 'message' => 'Not your trip'], 403);
        }

        $negotiation = \App\Models\TripNegotiation::where('id', $data['negotiation_id'])
            ->where('trip_id', $trip->id)
            ->firstOrFail();

        $negotiation->update(['status' => 'rejected']);

        $trip->update([
            'negotiation_status' => 'rejected', // Or pending if they reject one, but keep trip pending overall
        ]);

        broadcast(new \App\Events\NegotiationRejected($trip, $negotiation))->toOthers();

        $trip->load('driver');
        $this->notificationService->notifyNegotiationRejected($trip, 'client');

        return response()->json([
            'status' => true,
            'message' => 'Offer rejected',
            'negotiation' => $negotiation,
        ]);
    }
    public function rateDriver(Request $request, Trip $trip)
    {
        $client = $request->user();

        $data = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string',
        ]);

        // 1) تأكيد إن العميل صاحب الرحلة
        if ($trip->client_id !== $client->id) {
            return response()->json(['status' => false, 'message' => 'Not your trip'], 403);
        }

        // 2) الرحلة لازم تكون مدفوعة
        // if ($trip->status !== 'paid') {
        //     return response()->json(['status' => false, 'message' => 'Trip not paid yet'], 400);
        // }

        // 3) السائق لازم يكون موجود
        if (!$trip->driver_id) {
            return response()->json(['status' => false, 'message' => 'No driver assigned'], 400);
        }

        // 4) حفظ التقييم
        Rating::create([
            'trip_id' => $trip->id,
            'rated_user_id' => $trip->driver_id,
            'rated_by_user_id' => $client->id,
            'rated_by' => 'client',
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? null,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Driver rated successfully',
        ]);
    }
}
