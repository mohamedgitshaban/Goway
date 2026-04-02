<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Events\TripAccepted;
use App\Events\TripLocked;
use App\Models\Rating;

class DriverTripController extends Controller
{
    public function accept(Request $request, Trip $trip)
    {
        $driver = $request->user();

        // 1) تحقق إن السائق Driver
        if ($driver->usertype !== 'driver') {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        return DB::transaction(function () use ($trip, $driver) {

            // 2) تحقق إن الرحلة لسه searching
            if ($trip->status !== 'searching_driver') {
                return response()->json([
                    'status' => false,
                    'message' => 'Trip already accepted by another driver'
                ], 409);
            }

            // 3) تحقق إن السائق Online
            if ($driver->online_status !== 'online') {
                return response()->json([
                    'status' => false,
                    'message' => 'Driver is offline'
                ], 400);
            }

            // 4) تحقق إن السائق مشغول برحلة تانية
            $activeTrip = Trip::where('driver_id', $driver->id)
                ->whereIn('status', ['driver_assigned', 'driver_arrived', 'in_progress'])
                ->first();

            if ($activeTrip) {
                return response()->json([
                    'status' => false,
                    'message' => 'Driver already has an active trip'
                ], 400);
            }

            // 5) قبول الرحلة
            $trip->update([
                'driver_id' => $driver->id,
                'status' => 'driver_assigned',
                'driver_assigned_at' => now(),
            ]);

            // 6) إرسال Event للعميل
            broadcast(new TripAccepted($trip))->toOthers();

            // 7) إرسال Event لباقي السائقين
            broadcast(new TripLocked($trip->id))->toOthers();

            return response()->json([
                'status' => true,
                'message' => 'Trip accepted successfully',
                'trip' => $trip,
                'trip_channel' => "trip.{$trip->id}",
            ]);
        });
    }
    public function arrived(Request $request, Trip $trip)
    {
        $driver = $request->user();

        // 1) تأكيد إن المستخدم Driver
        if ($driver->usertype !== 'driver') {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        // 2) تأكيد إن السائق هو صاحب الرحلة
        if ($trip->driver_id !== $driver->id) {
            return response()->json(['status' => false, 'message' => 'Not your trip'], 403);
        }

        // 3) الرحلة لازم تكون driver_assigned
        if ($trip->status !== 'driver_assigned') {
            return response()->json([
                'status' => false,
                'message' => 'Trip is not ready for arrival'
            ], 400);
        }

        // 4) تحديث حالة الرحلة
        $trip->update([
            'status' => 'driver_arrived',
            'driver_arrived_at' => now(),
        ]);

        // 5) إرسال Event للعميل
        broadcast(new \App\Events\DriverArrived($trip))->toOthers();

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

        // 1) تأكيد إن المستخدم Driver
        if ($driver->usertype !== 'driver') {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        // 2) تأكيد إن السائق هو صاحب الرحلة
        if ($trip->driver_id !== $driver->id) {
            return response()->json(['status' => false, 'message' => 'Not your trip'], 403);
        }

        // 3) الرحلة لازم تكون driver_arrived
        if ($trip->status !== 'driver_arrived') {
            return response()->json([
                'status' => false,
                'message' => 'Trip cannot be started at this stage'
            ], 400);
        }

        // 4) تحديث حالة الرحلة
        $trip->update([
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        // 5) إرسال Event للعميل
        broadcast(new \App\Events\TripStarted($trip))->toOthers();

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

        // 1) تأكيد إن المستخدم Driver
        if ($driver->usertype !== 'driver') {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        // 2) تأكيد إن السائق هو صاحب الرحلة
        if ($trip->driver_id !== $driver->id) {
            return response()->json(['status' => false, 'message' => 'Not your trip'], 403);
        }

        // 3) الرحلة لازم تكون in_progress
        if ($trip->status !== 'in_progress') {
            return response()->json([
                'status' => false,
                'message' => 'Trip cannot be completed at this stage'
            ], 400);
        }

        // 4) حساب الوقت الفعلي
        $startedAt = $trip->started_at;
        $completedAt = now();
        $durationMinutes = $startedAt ? $startedAt->diffInMinutes($completedAt) : 0;

        // 5) تحديث حالة الرحلة
        $trip->update([
            'status' => 'completed',
            'completed_at' => $completedAt,
            'duration_minutes' => $durationMinutes,
        ]);

        // 6) إرسال Event للعميل
        broadcast(new \App\Events\TripCompleted($trip))->toOthers();

        return response()->json([
            'status' => true,
            'message' => 'Trip completed successfully',
            'trip_id' => $trip,
            'duration_minutes' => $durationMinutes,
            'trip_channel' => "trip.{$trip->id}",
        ]);
    }
    public function cancel(Request $request, Trip $trip)
    {
        $driver = $request->user();

        // 1) تأكيد إن السائق هو صاحب الرحلة
        if ($trip->driver_id !== $driver->id) {
            return response()->json(['status' => false, 'message' => 'Not your trip'], 403);
        }

        // 2) لا يمكن الإلغاء بعد بدء الرحلة
        if (! in_array($trip->status, ['searching_driver', 'driver_assigned', 'driver_arrived'])) {
            return response()->json([
                'status' => false,
                'message' => 'Trip cannot be cancelled at this stage'
            ], 400);
        }

        // 3) تحديث حالة الرحلة
        $trip->update([
            'status' => 'cancelled_by_driver',
            'cancelled_at' => now(),
            'cancelled_by' => 'driver',
            'cancel_reason' => $request->cancel_reason ?? null,
            'cancel_description' => $request->cancel_description ?? null,
        ]);

        // 4) إرسال Event للعميل
        broadcast(new \App\Events\TripCancelled($trip))->toOthers();

        return response()->json([
            'status' => true,
            'message' => 'Trip cancelled successfully',
            'trip_id' => $trip->id,
        ]);
    }
    public function negotiate(Request $request, Trip $trip)
    {
        $driver = $request->user();

        $data = $request->validate([
            'proposed_price' => 'required|numeric|min:1',
        ]);

        // 1) تأكيد إن السائق هو صاحب الرحلة
        if ($trip->driver_id !== $driver->id) {
            return response()->json(['status' => false, 'message' => 'Not your trip'], 403);
        }

        // 2) التفاوض مسموح؟
        if (! $trip->negotiation_enabled) {
            return response()->json(['status' => false, 'message' => 'Negotiation not allowed'], 400);
        }

        // 3) الرحلة في حالة تسمح بالتفاوض؟
        if (! in_array($trip->status, ['searching_driver', 'driver_assigned', 'driver_arrived'])) {
            return response()->json(['status' => false, 'message' => 'Cannot negotiate at this stage'], 400);
        }

        // 4) حفظ العرض
        $trip->update([
            'negotiation_price' => $data['proposed_price'],
            'negotiation_status' => 'pending',
        ]);

        // 5) إرسال Event للعميل
        broadcast(new \App\Events\NegotiationOffer($trip))->toOthers();

        return response()->json([
            'status' => true,
            'message' => 'Offer sent to client',
            'proposed_price' => $data['proposed_price'],
        ]);
    }
    public function rateClient(Request $request, Trip $trip)
    {
        $driver = $request->user();

        $data = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string',
        ]);

        // 1) تأكيد إن السائق صاحب الرحلة
        if ($trip->driver_id !== $driver->id) {
            return response()->json(['status' => false, 'message' => 'Not your trip'], 403);
        }

        // 2) الرحلة لازم تكون مدفوعة
        if ($trip->status !== 'paid') {
            return response()->json(['status' => false, 'message' => 'Trip not paid yet'], 400);
        }

        // 3) حفظ التقييم
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
