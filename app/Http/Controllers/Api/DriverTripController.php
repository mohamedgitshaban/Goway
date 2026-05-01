<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use Illuminate\Http\Request;
use App\Models\Rating;
use App\Http\Resources\TripResource;
use App\Services\NotificationService;
use App\Repositories\TripRepository;

class DriverTripController extends Controller
{
    public function __construct(
        protected TripRepository $trips,
        protected NotificationService $notificationService
    ) {}

    public function accept(Request $request, Trip $trip)
    {
        $driver = $request->user();

        if ($driver->usertype !== 'driver') {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        $result = $this->trips->assignDriver($trip, $driver);

        if (!empty($result['status']) && $result['status'] === true) {
            return response()->json($result);
        }

        $message = $result['message'] ?? 'Failed to accept trip';
        $statusCode = 409;
        if (str_contains(strtolower($message), 'offline') || str_contains(strtolower($message), 'active trip')) {
            $statusCode = 400;
        }

        return response()->json(['status' => false, 'message' => $message , 'trip' => $trip], $statusCode);
    }

    public function index(Request $request)
    {
        $driver = $request->user();

        if ($driver->usertype !== 'driver') {
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
            ->where('driver_id', $driver->id);

        if ($search) {
            $query->where(function ($q) use ($search) {
                if (is_numeric($search)) {
                    $q->where('id', $search);
                }

                $q->orWhereHas('client', function ($qc) use ($search) {
                    $qc->where('name', 'LIKE', "%{$search}%")
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

    public function arrived(Request $request, Trip $trip)
    {
        $driver = $request->user();

        if ($driver->usertype !== 'driver') {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        if ($trip->driver_id !== $driver->id) {
            return response()->json(['status' => false, 'message' => 'Not your trip'], 403);
        }

        if ($trip->status !== 'driver_assigned') {
            return response()->json(['status' => false, 'message' => 'Trip is not ready for arrival'], 400);
        }

        $trip->update([
            'status' => 'driver_arrived',
            'driver_arrived_at' => now(),
        ]);

        broadcast(new \App\Events\DriverArrived($trip))->toOthers();

        $trip->load('client');
        $this->notificationService->notifyDriverArrived($trip);

        return response()->json([
            'status' => true,
            'message' => 'Driver marked as arrived',
            'trip' => $trip,
            'trip_channel' => "trip.{$trip->id}",
        ]);
    }

    public function start(Request $request, Trip $trip)
    {
        $driver = $request->user();

        if ($driver->usertype !== 'driver') {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        if ($trip->driver_id !== $driver->id) {
            return response()->json(['status' => false, 'message' => 'Not your trip'], 403);
        }

        if ($trip->status !== 'driver_arrived') {
            return response()->json(['status' => false, 'message' => 'Trip cannot be started at this stage'], 400);
        }

        $trip->update([
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        broadcast(new \App\Events\TripStarted($trip))->toOthers();

        $trip->load('client');
        $this->notificationService->notifyTripStarted($trip);

        return response()->json([
            'status' => true,
            'message' => 'Trip started successfully',
            'trip' => $trip,
            'trip_channel' => "trip.{$trip->id}",
        ]);
    }

    public function complete(Request $request, Trip $trip)
    {
        $data = $request->validate([
            'cost' => 'nullable|numeric|min:0',
        ]);
        $driver = $request->user();

        if ($driver->usertype !== 'driver') {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        if ($trip->driver_id !== $driver->id) {
            return response()->json(['status' => false, 'message' => 'Not your trip'], 403);
        }

        if ($trip->status !== 'in_progress') {
            return response()->json(['status' => false, 'message' => 'Trip cannot be completed at this stage'], 400);
        }
        if ($trip->payment_method === 'cash') {
            $profitMargin = $trip->tripType?->profit_margin ?? 0;
            $driverShare = $trip->final_price - ($trip->final_price * ($profitMargin / 100));
            $driverWallet = $driver->wallet()->first() ?: $driver->wallet()->create(['balance' => 0]);
            $driverWallet->decrement('balance', $driverShare);
        }
        if (isset($data['cost'])) {
            $trip->client->wallet()->increment('balance', $data['cost']-$trip->final_price);
        }
        $startedAt = $trip->started_at;
        $completedAt = now();
        $durationMinutes = $startedAt ? $startedAt->diffInMinutes($completedAt) : 0;

        $trip->update([
             'status' => 'paid',
            'paid_at' => now(),
            'status' => 'completed',
            'completed_at' => $completedAt,
            'duration_minutes' => $durationMinutes,
        ]);

        $trip->driver()->update(['is_idle' => true]);

        broadcast(new \App\Events\TripCompleted($trip))->toOthers();

        $trip->load(['client', 'driver']);
        $this->notificationService->notifyTripCompleted($trip);

        try {
            $clientId = $trip->client_id;
            if ($clientId) {
                $completedCount = Trip::where('client_id', $clientId)->where('status', 'completed')->count();
                if ($completedCount === 5) {
                    $client = $trip->client;
                    if ($client && $client->wallet) {
                        $client->wallet->increment('balance', 100);
                    }
                }
            }
        } catch (\Exception $e) {
            \Log::error('Failed to credit wallet on trip completion: ' . $e->getMessage());
        }

        return response()->json([
            'status' => true,
            'message' => 'Trip completed successfully',
            'trip' => $trip,
            'duration_minutes' => $durationMinutes,
            'trip_channel' => "trip.{$trip->id}",
        ]);
    }

    public function cancel(Request $request, Trip $trip)
    {
        $driver = $request->user();

        if ($trip->driver_id !== $driver->id) {
            return response()->json(['status' => false, 'message' => 'Not your trip'], 403);
        }

        if (! in_array($trip->status, ['searching_driver', 'driver_assigned', 'driver_arrived'])) {
            return response()->json(['status' => false, 'message' => 'Trip cannot be cancelled at this stage'], 400);
        }

        $reason = $request->cancel_reason ?? null;
        $desc = $request->cancel_description ?? null;

        $res = $this->trips->driverCancel($trip, $driver, $reason, $desc);

        return response()->json($res);
    }

    public function negotiate(Request $request, Trip $trip)
    {
        $driver = $request->user();

        $data = $request->validate([
            'proposed_price' => 'required|numeric|min:1',
        ]);


        if (! in_array($trip->status, ['searching_driver'])) {
            return response()->json(['status' => false, 'message' => 'Cannot negotiate at this stage'], 400);
        }

        $trip->update([
            'negotiation_price' => $data['proposed_price'],
            'negotiation_status' => 'pending',
        ]);

        $negotiation = \App\Models\TripNegotiation::updateOrCreate(
            ['trip_id' => $trip->id, 'driver_id' => $driver->id],
            ['proposed_price' => $data['proposed_price'], 'status' => 'pending']
        );
        
        $negotiation->load('driver');

        broadcast(new \App\Events\NegotiationOffer($trip, $negotiation))->toOthers();

        $trip->load('client');
        $this->notificationService->notifyNegotiationOffer($trip);

        return response()->json([
            'status' => true,
            'message' => 'Offer sent to client',
            'proposed_price' => $data['proposed_price'],
            'negotiation' => $negotiation
        ]);
    }

    public function rateClient(Request $request, Trip $trip)
    {
        $driver = $request->user();

        $data = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string',
        ]);

        if ($trip->driver_id !== $driver->id) {
            return response()->json(['status' => false, 'message' => 'Not your trip'], 403);
        }

        if ($trip->status !== 'paid') {
            return response()->json(['status' => false, 'message' => 'Trip not paid yet'], 400);
        }

        Rating::create([
            'trip_id' => $trip->id,
            'rated_user_id' => $trip->client_id,
            'rated_by_user_id' => $driver->id,
            'rated_by' => 'driver',
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? null,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Client rated successfully',
        ]);
    }
}
