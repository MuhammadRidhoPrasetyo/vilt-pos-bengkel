<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockAdjustmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'store' => $this->whenLoaded('store', fn () => [
                'id' => $this->store?->id,
                'name' => $this->store?->name,
            ]),
            'status' => $this->status ?? 'draft',
            'reference_number' => $this->reference_number,
            'occurred_at' => $this->occurred_at?->toDateTimeString(),
            'note' => $this->note,
            'posted_by' => $this->whenLoaded('postedBy', fn () => $this->postedBy ? [
                'id' => $this->postedBy->id,
                'name' => $this->postedBy->name,
            ] : null),
            'items_count' => $this->whenCounted('items'),
            'items' => StockAdjustmentItemResource::collection($this->whenLoaded('items')),
            'movements' => InventoryMovementResource::collection($this->whenLoaded('movements')),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
