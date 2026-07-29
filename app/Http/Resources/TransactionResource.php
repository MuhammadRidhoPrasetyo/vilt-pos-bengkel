<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'store_id' => $this->store_id,
            'store' => new StoreResource($this->whenLoaded('store')),
            'user_id' => $this->user_id,
            'user' => new UserResource($this->whenLoaded('user')),
            'customer_id' => $this->customer_id,
            'customer' => new PartnerResource($this->whenLoaded('customer')),
            'payment_id' => $this->payment_id,
            'payment' => new PaymentResource($this->whenLoaded('payment')),
            'transaction_date' => $this->transaction_date?->toDateTimeString(),
            'type' => $this->type,
            'service_order_id' => $this->service_order_id,
            'service_order' => new ServiceOrderResource($this->whenLoaded('serviceOrder')),
            'subtotal' => (float) $this->subtotal,
            'item_discount_total' => (float) $this->item_discount_total,
            'subtotal_after_item_discount' => (float) $this->subtotal_after_item_discount,
            'universal_discount_mode' => $this->universal_discount_mode,
            'universal_discount_value' => (float) $this->universal_discount_value,
            'universal_discount_amount' => (float) $this->universal_discount_amount,
            'tax_rate' => (float) $this->tax_rate,
            'tax_total' => (float) $this->tax_total,
            'grand_total' => (float) $this->grand_total,
            'paid_amount' => (float) $this->paid_amount,
            'change_amount' => (float) $this->change_amount,
            'payment_status' => $this->payment_status,
            'total_cost' => (float) $this->total_cost,
            'total_profit' => (float) $this->total_profit,
            'status' => $this->status,
            'note' => $this->note,
            'created_at' => $this->created_at?->toDateTimeString(),
            'items' => TransactionItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
