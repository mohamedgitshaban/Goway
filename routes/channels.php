<?php
// routes/channels.php
use Illuminate\Support\Facades\Broadcast;
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
| Driver Status — العميل + السائق
|--------------------------------------------------------------------------
*/
Broadcast::channel('driver.status.{driverId}', function (User $user, $driverId) {
    return $user->id == $driverId || $user->type === 'client';
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
