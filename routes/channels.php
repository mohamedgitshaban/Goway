<?php
// routes/channels.php
use Illuminate\Support\Facades\Broadcast;
use App\Models\Conversation;
use App\Models\Trip;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Trip Channel — العميل + السائق
|--------------------------------------------------------------------------
*/
Broadcast::channel('trip.{tripId}', function (User $user, $tripId) {
    $trip = Trip::find($tripId);
    if (! $trip) return false;

    return $user->id === $trip->client_id || $user->id === $trip->driver_id;
});

Broadcast::channel('trip.{tripId}.driver-location', function (User $user, $tripId) {
    $trip = Trip::find($tripId);
    if (! $trip) return false;

    return $user->id === $trip->client_id || $user->id === $trip->driver_id;
});

/*
|--------------------------------------------------------------------------
| Driver Requests — السائق فقط
|--------------------------------------------------------------------------
*/
Broadcast::channel('driver.requests.{driverId}', function (User $user, $driverId) {
    return $user->id == $driverId && $user->type === 'driver';
});

/*
|--------------------------------------------------------------------------
| Nearby Drivers — قناة عامة
|--------------------------------------------------------------------------
*/
Broadcast::channel('nearby.drivers.{geohash}', function () {
    return true;
});

Broadcast::channel('trip.locked', function () {
    return true;
});

/*
|--------------------------------------------------------------------------
| Chat Channel — المشاركين في المحادثة فقط
|--------------------------------------------------------------------------
*/
Broadcast::channel('chat.{conversationId}', function (User $user, $conversationId) {
    $conversation = Conversation::find($conversationId);
    if (! $conversation) return false;

    // Initiator
    if ($user->id === $conversation->user_id) return true;

    // Assigned admin
    if ($conversation->admin_id && $user->id === $conversation->admin_id) return true;

    // Any admin can listen to support conversations
    if ($user->isAdmin() && $conversation->isSupport()) return true;

    // Trip participants (client & driver)
    if ($conversation->trip_id && $conversation->trip) {
        return $user->id === $conversation->trip->client_id
            || $user->id === $conversation->trip->driver_id;
    }

    return false;
});

/*
|--------------------------------------------------------------------------
| User Chat Notifications — إشعارات المحادثات الجديدة
|--------------------------------------------------------------------------
| Each user subscribes to user.{id}.chat to receive alerts about
| new conversations (support replies, trip chat started, etc.)
*/
Broadcast::channel('user.{userId}.chat', function (User $user, $userId) {
    return (int) $user->id === (int) $userId;
});
