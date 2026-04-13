<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class VehicleResource extends JsonResource
{
    public function toArray($request)
    {
        // helper to normalize stored image paths to public URLs
        $normalize = function ($value) {
            if (! $value) {
                return null;
            }
            if (filter_var($value, FILTER_VALIDATE_URL)) {
                return $value;
            }
            return Storage::disk('public')->url($value);
        };

        return [
            'id' => $this->id,
            'driver_id' => $this->driver_id ?? null,
            'trip_type_id' => $this->trip_type_id ?? null,
            'vehicle_brand_id' => $this->vehicle_brand_id ?? null,
            'vehicle_model_id' => $this->vehicle_model_id ?? null,
            'plate_number' => $this->plate_number ?? null,
            'color' => $this->color ?? null,
            'year' => $this->year ?? null,
            // image fields
            'vehicle_license_image' => $normalize($this->vehicle_license_image ?? null),
            'car_front_image' => $normalize($this->car_front_image ?? null),
            'car_back_image' => $normalize($this->car_back_image ?? null),
            'car_left_image' => $normalize($this->car_left_image ?? null),
            'car_right_image' => $normalize($this->car_right_image ?? null),
            // status flags
            'isactive' => (bool) ($this->isactive ?? $this->is_active ?? false),
            'status' => $this->status ?? null,
            'rejection_reason' => $this->rejection_reason ?? null,

            'trip_type' => new \App\Http\Resources\TripTypeResource($this->tripType),
            'brand' => $this->brand,
            'model' => $this->model,
            'created_at' => optional($this->created_at)->toDateTimeString(),
            'updated_at' => optional($this->updated_at)->toDateTimeString(),
        ];
    }
}