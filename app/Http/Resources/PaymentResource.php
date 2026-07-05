<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'account_number' => $this->account_number,
            'account_name' => $this->account_name,
            'provider_code' => $this->provider_code,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
