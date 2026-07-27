<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'invoice_number' => $this->invoice_number,
            'purchase_date' => $this->purchase_date?->format('Y-m-d'),
            'store_id' => $this->store_id,
            'store' => new StoreResource($this->whenLoaded('store')),
            'supplier_id' => $this->supplier_id,
            'supplier' => new PartnerResource($this->whenLoaded('supplier')),
            'created_by' => $this->created_by,
            'creator' => new UserResource($this->whenLoaded('creator')),
            'received_by' => $this->received_by,
            'receiver' => new UserResource($this->whenLoaded('receiver')),
            'discount_type' => $this->discount_type,
            'discount_value' => $this->discount_value ? (float) $this->discount_value : 0,
            'price' => (float) $this->price,
            'notes' => $this->notes,
            'items' => PurchaseItemResource::collection($this->whenLoaded('items')),
            'items_count' => $this->whenCounted('items'),
            'cash_flows' => $this->whenLoaded('cashFlows'),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
