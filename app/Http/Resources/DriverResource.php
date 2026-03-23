<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DriverResource extends JsonResource
{
    public function toArray($request)
    {
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
            'personal_image'  => $this->personal_image,
            'documents' => $doc ? [
                'id'                => $doc->id,
                'age'               => $doc->age,
                'nid_front'         => $doc->nid_front,
                'nid_back'          => $doc->nid_back,
                'parent_nid_front'  => $doc->parent_nid_front,
                'parent_nid_back'   => $doc->parent_nid_back,
                'license_image'     => $doc->license_image,
                'criminal_record'   => $doc->criminal_record,
                'status'            => $doc->status,
                'reject_reason'     => $doc->reject_reason,
            ] : null,
        ];
    }
}
