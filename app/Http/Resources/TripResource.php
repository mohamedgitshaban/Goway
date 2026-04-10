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

            'distance_km' => $this->distance_km,
            'base_fare' => (float) $this->base_fare,
            'price_per_km' => (float) $this->price_per_km,
            'original_price' => (float) $this->original_price,
            'discount_amount' => (float) $this->discount_amount,
            'final_price' => (float) $this->final_price, 
            'negotiation_enabled' => $this->negotiation_enabled,
            'negotiated_price_before' => (float)  $this->negotiated_price_before,
            'negotiated_price_after' => (float)  $this->negotiated_price_after,
            'negotiation_price' => (float)  $this->negotiation_price,
            'negotiation_status' => $this->negotiation_status,

            'origin_lat' => (float) $this->origin_lat,
            'origin_lng' => (float) $this->origin_lng,
            'origin_address' => $this->origin_address,

            'destination_lat' => (float) $this->destination_lat,
            'destination_lng' => (float) $this->destination_lng,
            'destination_address' => $this->destination_address,

            'driver_assigned_at' => $this->driver_assigned_at?->toISOString(),
            'driver_arrived_at' => $this->driver_arrived_at?->toISOString(),
            'started_at' => $this->started_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'cancelled_at' => $this->cancelled_at?->toISOString(),
            'cancelled_by' => $this->cancelled_by,
            'cancel_reason' => $this->cancel_reason,
            'cancel_description' => $this->cancel_description,

            // 🔵 Relations
            'client' => new ClientResource($this->whenLoaded('client')),
            'driver' => new DriverResource($this->whenLoaded('driver')),
            'trip_type' => new TripTypeResource($this->whenLoaded('tripType')),
            'waypoints' => TripWaypointResource::collection($this->whenLoaded('waypoints')),
            'offer' => new OfferResource($this->whenLoaded('offer')),
            'coupon' => new CouponResource($this->whenLoaded('coupon')),
        ];
    }
}
