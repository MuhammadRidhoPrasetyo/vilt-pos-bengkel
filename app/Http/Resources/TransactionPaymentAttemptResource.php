<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionPaymentAttemptResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'transaction_id' => $this->transaction_id,
            'user_id' => $this->user_id,
            'user' => new UserResource($this->whenLoaded('user')),
            'payment_id' => $this->payment_id,
            'payment' => new PaymentResource($this->whenLoaded('payment')),
            'amount' => (float) $this->amount,
            'amount_given' => (float) $this->amount_given,
            'change' => (float) $this->change,
            'paid_at' => $this->paid_at?->toDateTimeString(),
            'metadata' => $this->metadata,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
