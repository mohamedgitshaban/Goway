<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DriverPayoutAccountResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'             => $this->id,
            'payment_method' => $this->payment_method,
            // Fields below are stored encrypted in the DB.
            // The model's 'encrypted' cast decrypts them automatically on read.
            'account_name'   => $this->account_name,
            'account_number' => $this->account_number,
            'bank_name'      => $this->bank_name,
            'iban'           => $this->iban,
            'updated_at'     => $this->updated_at->toISOString(),
        ];
    }
}
