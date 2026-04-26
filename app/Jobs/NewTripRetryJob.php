<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Trip;
use App\Events\NewTripRequest;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Redis;
use App\Support\GeoHash;

class NewTripRetryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tripId;
    public int $attemptsMade;

    public function __construct(int $tripId, int $attemptsMade = 0)
    {
        $this->tripId = $tripId;
        $this->attemptsMade = $attemptsMade;
    }

    public function handle()
    {
        $trip = Trip::find($this->tripId);
        if (! $trip) return;

        // stop if trip already assigned or not searching
        if ($trip->driver_id || $trip->status !== 'searching_driver') return;

        $originGeohash = GeoHash::encode($trip->origin_lat, $trip->origin_lng, 7);
        $cells = array_merge([$originGeohash], GeoHash::neighbors($originGeohash));

        $nearbyDrivers = [];
        foreach ($cells as $cell) {
            $nearbyDrivers = array_merge($nearbyDrivers, Redis::smembers("geohash:drivers:{$cell}"));
        }

        $nearbyDrivers = array_unique($nearbyDrivers);

        $notification = app(NotificationService::class);

        foreach ($nearbyDrivers as $driverId) {
            $driver = \App\Models\Driver::find($driverId);
            if ($driver && $driver->is_online) {
                broadcast(new NewTripRequest($trip, $driverId));
                $notification->notifyNewTripRequest($trip, $driver);
            }
        }

        // schedule next retry if still unassigned and attempts < 9 (so total 10 tries)
        if ($this->attemptsMade < 9) {
            self::dispatch($this->tripId, $this->attemptsMade + 1)->delay(now()->addMinutes(5));
        }
    }
}
