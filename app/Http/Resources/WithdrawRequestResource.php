<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class WithdrawRequestResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'               => $this->id,
            'amount'           => $this->amount,
            'status'           => $this->status,
            'payment_method'   => $this->payment_method,
            'account_name'     => $this->account_name,
            'account_number'   => $this->account_number,
            'bank_name'        => $this->bank_name,
            'iban'             => $this->iban,
            'admin_note'       => $this->admin_note,
            'payout_reference' => $this->payout_reference,
            'processed_at'     => $this->processed_at?->toISOString(),
            'created_at'       => $this->created_at->toISOString(),
            // Admin-facing extras
            'driver'           => $this->when(
                $this->relationLoaded('user'),
                fn() => [
                    'id'    => $this->user->id,
                    'name'  => $this->user->name,
                    'phone' => $this->user->phone ?? null,
                ]
            ),
            'reviewed_by' => $this->when(
                $this->relationLoaded('admin') && $this->admin,
                fn() => [
                    'id'   => $this->admin->id,
                    'name' => $this->admin->name,
                ]
            ),
        ];
    }
}
