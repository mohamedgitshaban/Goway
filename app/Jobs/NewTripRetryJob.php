<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Trip;
use App\Events\NewTripRequest;
use App\Models\Driver;
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

        $originGeohash = GeoHash::encode($trip->origin_lat, $trip->origin_lng, 5);
        $cells = array_merge([$originGeohash], GeoHash::neighbors($originGeohash));

        $nearbyDrivers = [];
        foreach ($cells as $cell) {
            $nearbyDrivers = array_merge($nearbyDrivers, Redis::smembers("geohash:drivers:{$cell}"));
        }

        $nearbyDrivers = array_unique($nearbyDrivers);

        $notification = app(NotificationService::class);

            if (! empty($nearbyDrivers)) {
                $drivers = \App\Models\Driver::whereIn('id', $nearbyDrivers)
                    ->where('is_online', 1)
                    ->where('is_idle', 1)
                    ->whereHas('vehicles', function ($query) use ($trip) {
                        $query->where('isactive', 1);
                        $query->where('trip_type_id', $trip->trip_type_id);
                    })
                    ->get();
                foreach ($drivers as $driver) {
                    broadcast(new NewTripRequest($trip, $driver->id));
                    $notification->notifyNewTripRequest($trip, $driver);
                }
            }
        

        // schedule next retry if still unassigned and attempts < 9 (so total 10 tries)
        if ($this->attemptsMade < 9) {
            self::dispatch($this->tripId, $this->attemptsMade + 1)->delay(now()->addMinutes(2));
        }
    }
}
