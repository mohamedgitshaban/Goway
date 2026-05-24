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
use App\Services\SurgePricingService;
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
                           $drivers = Driver::with('destinationPreference')->whereIn('id', $nearbyDrivers)->where('is_online', 1)->where('is_idle', 1)->whereHas('activeVehicle', function ($query) use ($trip) {
                $query->where('trip_type_id', $trip->trip_type_id);
            })->get();

            $filteredDrivers = [];
            foreach ($drivers as $driver) {
                if ($driver->destinationPreference) {
                    $routeData = app(\App\Services\GoogleRouteService::class)->compute([
                        'origin_lat' => (float) $trip->destination_lat,
                        'origin_lng' => (float) $trip->destination_lng,
                        'destination_lat' => (float) $driver->destinationPreference->lat,
                        'destination_lng' => (float) $driver->destinationPreference->lng,
                        'waypoints' => [],
                    ]);
                    
                    $distanceToPref = isset($routeData['distance_km']) ? (float) $routeData['distance_km'] : GeoHash::distanceKm(
                        $trip->destination_lat,
                        $trip->destination_lng,
                        $driver->destinationPreference->lat,
                        $driver->destinationPreference->lng
                    );
                    
                    // Allow if the trip's destination is within 20km of the driver's preferred destination (meaning it's on the way or close)
                    if ($distanceToPref <= 20) {
                        $filteredDrivers[] = $driver;
                    }
                } else {
                    $filteredDrivers[] = $driver;
                }
            }
                foreach ($filteredDrivers as $driver) {
                    broadcast(new NewTripRequest($trip, $driver->id , 'new_trip_request_retry'));
                    $notification->notifyNewTripRequest($trip, $driver);
                }
            }
        

        // schedule next retry if still unassigned and attempts < 20 (so total 20 tries)
        if ($this->attemptsMade < 20) {
            self::dispatch($this->tripId, $this->attemptsMade + 1)->delay(now()->addMinutes(2));
        }
        else {
            // Mark trip as failed after 20 attempts
            $trip->status = 'cancelled_by_system';
            $trip->cancelled_at = now();
            $trip->cancelled_by = null; // System
            $trip->cancel_reason = 'no_drivers_found';
            $trip->save();

            $surgePricing = app(SurgePricingService::class);
            Redis::srem($surgePricing->demandKey($originGeohash), $trip->id);
            Redis::srem($surgePricing->tripTypeDemandKey($originGeohash, (int) $trip->trip_type_id), $trip->id);

            broadcast(new \App\Events\TripCancelledBySystem($trip, $nearbyDrivers));
        }
    }
}
