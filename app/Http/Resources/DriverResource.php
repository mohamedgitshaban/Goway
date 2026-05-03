<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DriverResource extends JsonResource
{
    public function toArray($request)
    {
                $activeTrip = \App\Models\Trip::where('driver_id', $this->id)
            ->whereIn('status', ['pending', 'searching_driver', 'driver_assigned', 'driver_arrived', 'in_progress'])
            ->first();
        $doc = $this->driverDocument; // one-to-one relation
        return [
            'id'         => $this->id,
            'first_name' => $this->first_name,
            'last_name'  => $this->last_name,
            'full_name'       => $this->name,
            'phone'      => $this->phone,
            'email'      => $this->email,
            'usertype'   => $this->usertype,
            'status'     => $this->status,
            'is_online'  => $this->is_online,
            'personal_image'  => $this->personal_image,
            'wallet' => $this->wallet ? [
                'id' => $this->wallet->id,
                'balance' => (float) $this->wallet->balance,
            ] : null,
            'documents' => $doc ? new DriverDocumentResource($doc) : null,
                'vehicles' => VehicleResource::collection($this->vehicles),
                'active_vehicle' => $this->activeVehicle ? new VehicleResource($this->activeVehicle) : null,
                'current_trip' => empty($this->without_trip) && $activeTrip ? new TripResource($activeTrip) : null,
        ];
    }
}
