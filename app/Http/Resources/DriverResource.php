<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DriverResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'         => $this->id,
            'first_name' => $this->first_name,
            'last_name'  => $this->last_name,
            'full_name'       => $this->name,
            'phone'      => $this->phone,
            'email'      => $this->email,
            'usertype'   => $this->usertype,
            'status'     => $this->status,
            'personal_image'  => $this->personal_image,
            'documents'  => [
                'nid_front'       => $this->nid_front,
                'nid_back'        => $this->nid_back,
                'license_image'   => $this->license_image,
                'criminal_record' => $this->criminal_record,
            ],
        ];
    }
}
