<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockTransferResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'from_store_id' => $this->from_store_id,
            'to_store_id' => $this->to_store_id,
            'from_store' => $this->whenLoaded('fromStore', fn () => ['id' => $this->fromStore?->id, 'name' => $this->fromStore?->name]),
            'to_store' => $this->whenLoaded('toStore', fn () => ['id' => $this->toStore?->id, 'name' => $this->toStore?->name]),
            'status' => $this->status,
            'reference_number' => $this->reference_number,
            'occurred_at' => $this->occurred_at?->toDateTimeString(),
            'posted_at' => $this->posted_at?->toDateTimeString(),
            'note' => $this->note,
            'created_by' => $this->whenLoaded('createdBy', fn () => $this->createdBy ? ['id' => $this->createdBy->id, 'name' => $this->createdBy->name] : null),
            'posted_by' => $this->whenLoaded('postedBy', fn () => $this->postedBy ? ['id' => $this->postedBy->id, 'name' => $this->postedBy->name] : null),
            'items_count' => $this->whenCounted('items'),
            'items' => StockTransferItemResource::collection($this->whenLoaded('items')),
            'movements' => InventoryMovementResource::collection($this->whenLoaded('movements')),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
