<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DriverDocumentResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'        => $this->id,
            'age'       => $this->age,
            'status'    => $this->status,
            'reject_reason' => $this->reject_reason,

            // Driver info
            'driver' => [
                'id'    => $this->driver->id,
                'name'  => $this->driver->name,
                'phone' => $this->driver->phone,
            ],

            // Trip type info
            'trip_type' => [
                'id'   => $this->tripType?->id,
                'name' => $this->tripType?->name,   // ⭐ SEARCHABLE FIELD
            ],

            // Document URLs
            'nid_front'         => $this->nid_front,
            'nid_back'          => $this->nid_back,
            'birth_front'       => $this->birth_front,
            'parent_nid_front'  => $this->parent_nid_front,
            'parent_nid_back'   => $this->parent_nid_back,
            'license_image'     => $this->license_image,
            'criminal_record'   => $this->criminal_record,
        ];
    }
}
