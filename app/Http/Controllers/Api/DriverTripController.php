<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TripNegotiationResource;
use App\Models\Trip;
use Illuminate\Http\Request;
use App\Models\Rating;
use App\Http\Resources\TripResource;
use App\Services\NotificationService;
use App\Repositories\TripRepository;
use App\Services\SurgePricingService;
use App\Services\WalletService;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class DriverTripController extends Controller
{
    public function __construct(
        protected TripRepository $trips,
        protected NotificationService $notificationService,
        protected WalletService $walletService,
        protected SurgePricingService $surgePricing
    ) {}

    public function surgeMap(Request $request)
    {
        $driver = $request->user();

        if (! $driver || $driver->usertype !== 'driver') {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
            'trip_type_id' => 'nullable|exists:trip_types,id',
        ]);

        $lat = $data['lat'] ?? null;
        $lng = $data['lng'] ?? null;

        if (($lat === null) xor ($lng === null)) {
            return response()->json([
                'status' => false,
                'message' => 'lat and lng must be provided together',
            ], 422);
        }

        if ($lat === null && $lng === null) {
            $state = Redis::hmget("driver:{$driver->id}:location", ['lat', 'lng']);
            $lat = $state[0] ?? null;
            $lng = $state[1] ?? null;
        }

        if ($lat === null || $lng === null) {
            return response()->json([
                'status' => false,
                'message' => 'No location found for driver. Send location or pass lat/lng.',
            ], 422);
        }

        $tripTypeId = isset($data['trip_type_id']) ? (int) $data['trip_type_id'] : null;
        $map = $this->surgePricing->getMapMultipliers((float) $lat, (float) $lng, $tripTypeId);

        return response()->json([
            'status' => true,
            'data' => $map,
        ]);
    }

    public function accept(Request $request, Trip $trip)
    {
        $driver = $request->user();

        $result = $this->trips->assignDriver($trip, $driver);

        if (!empty($result['status']) && $result['status'] === true) {
            return response()->json($result);
        }

        $message = $result['message'] ?? 'Failed to accept trip';
        $statusCode = 409;
        if (str_contains(strtolower($message), 'offline') || str_contains(strtolower($message), 'active trip')) {
            $statusCode = 400;
        }

        return response()->json(['status' => false, 'message' => $message, 'trip' => $trip], $statusCode);
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

    public function completedAverageLast7Days(Request $request)
    {
        $driver = $request->user();

        if (! $driver || $driver->usertype !== 'driver') {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
        ]);

        $endDate = !empty($validated['to']) ? \Carbon\Carbon::parse($validated['to'])->startOfDay() : today();
        $startDate = !empty($validated['from']) ? \Carbon\Carbon::parse($validated['from'])->startOfDay() : $endDate->copy()->subDays(6);

        $days = (int) $startDate->diffInDays($endDate) + 1;

        $daily = [];
        $sumCompleted = 0;
        $sumEffectiveTotal = 0;
        $sumDailyAverages = 0;

        for ($i = 0; $i < $days; $i++) {
            $date = $startDate->copy()->addDays($i);

            $baseQuery = Trip::where('driver_id', $driver->id)
                ->whereDate('created_at', $date);

            $totalTrips = (clone $baseQuery)->count();
            $completedTrips = (clone $baseQuery)->where('status', 'completed')->count();
            $cancelledByClient = (clone $baseQuery)->where('status', 'cancelled_by_client')->count();

            $effectiveTotal = max(0, $totalTrips - $cancelledByClient);
            $dailyAverage = $effectiveTotal > 0 ? round($completedTrips / $effectiveTotal, 4) : 0.0;

            $daily[] = [
                'date' => $date->toDateString(),
                'completed_trips' => $completedTrips,
                'total_trips' => $totalTrips,
                'cancelled_by_client' => $cancelledByClient,
                'effective_total' => $effectiveTotal,
                'average' => $dailyAverage,
            ];

            $sumCompleted += $completedTrips;
            $sumEffectiveTotal += $effectiveTotal;
            $sumDailyAverages += $dailyAverage;
        }

        $overallAverage = $sumEffectiveTotal > 0 ? round($sumCompleted / $sumEffectiveTotal, 4) : 0.0;
        $averageOfDaily = $days > 0 ? round($sumDailyAverages / $days, 4) : 0.0;

        return response()->json([
            'status' => true,
            'formula' => 'completed_trips / (total_trips - cancelled_by_client)',
            'period' => [
                'from' => $startDate->toDateString(),
                'to' => $endDate->toDateString(),
                'days' => $days,
            ],
            'daily' => $daily,
            'overall_average' => $overallAverage,
            'average_of_daily' => $averageOfDaily,
        ]);
    }

    public function earningsStats(Request $request)
    {
        $driver = $request->user();

        if (! $driver || $driver->usertype !== 'driver') {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'from' => 'nullable|date_format:j-n-Y',
            'to' => 'nullable|date_format:j-n-Y',
        ]);

        $fromDate = ! empty($validated['from'])
            ? \Carbon\Carbon::createFromFormat('!j-n-Y', $validated['from'])->toDateString()
            : null;
        $toDate = ! empty($validated['to'])
            ? \Carbon\Carbon::createFromFormat('!j-n-Y', $validated['to'])->toDateString()
            : null;

        if ($fromDate && $toDate && $toDate < $fromDate) {
            return response()->json([
                'status' => false,
                'message' => 'The to date must be after or equal to from date.',
            ], 422);
        }

        $completedTripsQuery = Trip::query()
            ->where('driver_id', $driver->id)
            ->whereIn('status', ['completed', 'paid']);

        if ($fromDate) {
            $completedTripsQuery->whereDate('completed_at', '>=', $fromDate);
        }

        if ($toDate) {
            $completedTripsQuery->whereDate('completed_at', '<=', $toDate);
        }

        $cashEarnings = (float) (clone $completedTripsQuery)
            ->where('payment_method', 'cash')
            ->sum('reminder');

        $walletTripEarningsQuery = WalletTransaction::query()
            ->where('user_id', $driver->id)
            ->where('user_type', 'driver')
            ->where('type', 'mint')
            ->where('source', 'trip.assign_driver_credit');

        if ($fromDate) {
            $walletTripEarningsQuery->whereDate('created_at', '>=', $fromDate);
        }

        if ($toDate) {
            $walletTripEarningsQuery->whereDate('created_at', '<=', $toDate);
        }

        $walletTripEarnings = (float) $walletTripEarningsQuery->sum('amount');

        $compensationQuery = WalletTransaction::query()
            ->where('user_id', $driver->id)
            ->where('user_type', 'driver')
            ->where('type', 'mint')
            ->where('source', 'trip.trip_cancelled_by_client_fee');

        if ($fromDate) {
            $compensationQuery->whereDate('created_at', '>=', $fromDate);
        }

        if ($toDate) {
            $compensationQuery->whereDate('created_at', '<=', $toDate);
        }

        $compensation = (float) $compensationQuery->sum('amount');

        $tripsCount = (int) (clone $completedTripsQuery)->count();
        $totalDistanceKm = (float) (clone $completedTripsQuery)->sum('distance_km');

        $totalIncome = $cashEarnings + $walletTripEarnings + $compensation;
        $earningsPerKilometer = $totalDistanceKm > 0 ? ($totalIncome / $totalDistanceKm) : 0.0;
        $incomePerTrip = $tripsCount > 0 ? ($totalIncome / $tripsCount) : 0.0;

        return response()->json([
            'status' => true,
            'period' => [
                'from' => $fromDate,
                'to' => $toDate,
            ],
            'metrics' => [
                'cash_paid_trips_earnings' => round($cashEarnings, 2),
                'wallet_added_trips_earnings' => round($walletTripEarnings, 2),
                'trips_count' => $tripsCount,
                'earnings_per_kilometer' => round($earningsPerKilometer, 2),
                'income_per_trip' => round($incomePerTrip, 2),
                'compensation' => round($compensation, 2),
                'total_income' => round($totalIncome, 2),
                'total_distance_km' => round($totalDistanceKm, 2),
            ],
        ]);
    }

    public function dailyIncome(Request $request)
    {
        $driver = $request->user();

        
        $today = now();
        // Daily amount based on original price minus driver share.
        $income = (float) Trip::query()
            ->where('driver_id', $driver->id)
            ->sum('driver_credit_deposed_amount');
        return response()->json([
            'status' => true,
            'date' => $income,
        ]);
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
        $driver = $request->user();

        if ($trip->driver_id !== $driver->id) {
            return response()->json(['status' => false, 'message' => 'Not your trip'], 403);
        }

        if ($trip->status !== 'in_progress') {
            return response()->json(['status' => false, 'message' => 'Trip cannot be completed at this stage'], 400);
        }
        $startedAt = $trip->started_at;
        $completedAt = now();
        $durationMinutes = $startedAt ? $startedAt->diffInMinutes($completedAt) : 0;

        $trip->update([
            'status' => 'completed',
            'completed_at' => $completedAt,
            'duration_minutes' => $durationMinutes,
        ]);

        $trip->driver()->update(['is_idle' => true]);
        
        // // Remove destination preference after trip completion
        // \App\Models\DriverDestinationPreference::where('driver_id', $driver->id)->delete();

        broadcast(new \App\Events\TripCompleted($trip))->toOthers();

        $trip->load(['client', 'driver']);
        $this->notificationService->notifyTripCompleted($trip);

        return response()->json([
            'status' => true,
            'message' => 'Trip completed successfully',
            'trip' => $trip,
            'duration_minutes' => $durationMinutes,
            'trip_channel' => "trip.{$trip->id}",
        ]);
    }
    public function paid(Request $request, Trip $trip)
    {
        $data = $request->validate([
            'cost' => ['nullable', 'numeric', 'min:' . $trip->reminder],
        ]);
        $driver = $request->user();

        if ($trip->driver_id !== $driver->id) {
            return response()->json(['status' => false, 'message' => 'Not your trip'], 403);
        }
        if ($trip->status != 'completed' && $trip->status != 'cancelled_by_client') {
            return response()->json(['status' => false, 'message' => 'Trip cannot be completed at this stage'], 400);
        }
        $cost = $data['cost'] && $data['cost'] > 0 ? $data['cost'] - $trip->reminder : 0;
        $this->trips->markTripAsPaid($trip, $cost);
        try {
            $clientId = $trip->client_id;
            if ($clientId) {
                $completedCount = Trip::where('client_id', $clientId)->where('status', 'paid')->count();
                if ($completedCount === 5) {
                    $client = $trip->client;
                    if ($client && $client->wallet) {
                        $this->walletService->increment($client, 100, 'trip.complete_bonus', [
                            'trip_id' => $trip->id,
                            'completed_trips_count' => 5,
                        ]);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to credit wallet on trip completion: ' . $e->getMessage());
        }

        return response()->json([
            'status' => true,
            'message' => 'Trip paid successfully',
            'trip' => $trip,
            'trip_channel' => "trip.{$trip->id}",
        ]);
    }
    public function cancel(Request $request, Trip $trip)
    {
        $driver = $request->user();

        if ($trip->driver_id !== $driver->id) {
            return response()->json(['status' => false, 'message' => 'Not your trip'], 403);
        }

        if (! in_array($trip->status, ['driver_assigned', 'driver_arrived' , 'in_progress'])) {
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
            ['proposed_price' => $data['proposed_price']-$trip->original_price + $trip->final_price, 'driver_proposed_price' => $data['proposed_price'], 'status' => 'pending']
        );

        broadcast(new \App\Events\NegotiationOffer($trip, $negotiation))->toOthers();

        $trip->load('client');
        $this->notificationService->notifyNegotiationOffer($trip);

        return response()->json([
            'status' => true,
            'message' => 'Offer sent to client',
            'proposed_price' => $data['proposed_price'],
            'negotiation' => new TripNegotiationResource($negotiation)
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
