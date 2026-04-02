<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TripResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'payment_method' => $this->payment_method,

            'client' => new ClientResource($this->whenLoaded('client')),
            'driver' => new DriverResource($this->whenLoaded('driver')),
            'trip_type' => new TripTypeResource($this->whenLoaded('tripType')),
            'waypoints' => TripWaypointResource::collection($this->whenLoaded('waypoints')),
            'offer' => new OfferResource($this->whenLoaded('offer')),
            'coupon' => new CouponResource($this->whenLoaded('coupon')),

            'origin_lat' => $this->origin_lat,
            'origin_lng' => $this->origin_lng,
            'destination_lat' => $this->destination_lat,
            'destination_lng' => $this->destination_lng,

            'driver_assigned_at' => $this->driver_assigned_at,
            'driver_arrived_at' => $this->driver_arrived_at,
            'started_at' => $this->started_at,
            'completed_at' => $this->completed_at,
        ];
    }
}
