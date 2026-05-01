<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
{
    public function toArray($request)
    {
        $activeTrip = \App\Models\Trip::where('client_id', $this->id)
            ->whereIn('status', ['pending', 'searching_driver', 'driver_assigned', 'driver_arrived', 'in_progress'])
            ->first();

        return [
            'id'         => $this->id,
            'first_name' => $this->first_name,
            'last_name'  => $this->last_name,
            'full_name'       => $this->name,
            'phone'      => $this->phone,
            'email'      => $this->email,
            'usertype'   => $this->usertype,
            'personal_image'  => $this->personal_image,
            'wallet' => $this->wallet ? [
                'id' => $this->wallet->id,
                'balance' => (float) $this->wallet->balance,
            ] : null,
            'status'     => $this->status,
            'current_trip' => empty($this->without_trip) && $activeTrip ? new TripResource($activeTrip) : null,
            
        ];
    }
}
