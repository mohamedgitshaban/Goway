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
use App\Support\GeoHash;
use Illuminate\Support\Facades\Redis;

class ClientTripController extends Controller
{
    public function __construct(
        protected TripRepository $trips,
        protected NotificationService $notificationService
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

        $trip = $this->trips->createTrip($user, $data);

        return response()->json([
            'status'  => true,
            'message' => 'Trip created successfully',
            'trip_id' => $trip->id,
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

        $distanceKm = $this->trips->calculateTripDistance($data);
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

        if ($trip->client_id !== $client->id) {
            return response()->json(['status' => false, 'message' => 'Not your trip'], 403);
        }

        if ($trip->negotiation_status !== 'pending') {
            return response()->json(['status' => false, 'message' => 'No pending offer'], 400);
        }

        $trip->update([
            'negotiated_price_before' => $trip->final_price,
            'negotiated_price_after' => $trip->negotiation_price,
            'final_price' => $trip->negotiation_price,
            'negotiation_status' => 'accepted',
        ]);

        broadcast(new \App\Events\NegotiationAccepted($trip))->toOthers();

        $trip->load('driver');
        $this->notificationService->notifyNegotiationAccepted($trip, 'client');

        return response()->json([
            'status' => true,
            'message' => 'Offer accepted',
            'final_price' => $trip->final_price,
        ]);
    }

    public function rejectNegotiation(Request $request, Trip $trip)
    {
        $client = $request->user();

        if ($trip->client_id !== $client->id) {
            return response()->json(['status' => false, 'message' => 'Not your trip'], 403);
        }

        $trip->update([
            'negotiation_status' => 'rejected',
        ]);

        broadcast(new \App\Events\NegotiationRejected($trip))->toOthers();

        $trip->load('driver');
        $this->notificationService->notifyNegotiationRejected($trip, 'client');

        return response()->json([
            'status' => true,
            'message' => 'Offer rejected',
        ]);
    }
    public function counterNegotiation(Request $request, Trip $trip)
    {
        $client = $request->user();

        $data = $request->validate([
            'counter_price' => 'required|numeric|min:1',
        ]);

        if ($trip->client_id !== $client->id) {
            return response()->json(['status' => false, 'message' => 'Not your trip'], 403);
        }

        $trip->update([
            'negotiation_status' => 'counter',
            'negotiation_price' => $data['counter_price'],
        ]);

        broadcast(new \App\Events\NegotiationCounter($trip))->toOthers();

        // Push notification to driver
        $trip->load('driver');
        $this->notificationService->notifyNegotiationCounter($trip);

        return response()->json([
            'status' => true,
            'message' => 'Counter offer sent',
            'counter_price' => $data['counter_price'],
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
