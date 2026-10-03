<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockOpnameResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'store' => $this->whenLoaded('store', fn () => [
                'id' => $this->store?->id,
                'name' => $this->store?->name,
                'code' => $this->store?->code,
            ]),
            'warehouse_id' => $this->warehouse_id,
            'warehouse' => $this->whenLoaded('warehouse', fn () => [
                'id' => $this->warehouse?->id,
                'name' => $this->warehouse?->name,
                'code' => $this->warehouse?->code,
            ]),
            'status' => $this->status ?? 'draft',
            'opname_number' => $this->opname_number,
            'occurred_at' => $this->occurred_at?->toDateTimeString(),
            'notes' => $this->notes,
            'total_system_qty' => (int) $this->total_system_qty,
            'total_physical_qty' => (int) $this->total_physical_qty,
            'total_difference_qty' => (int) $this->total_difference_qty,
            'total_difference_value' => (float) $this->total_difference_value,
            'created_by' => $this->whenLoaded('createdBy', fn () => $this->createdBy ? [
                'id' => $this->createdBy->id,
                'name' => $this->createdBy->name,
            ] : null),
            'posted_by' => $this->whenLoaded('postedBy', fn () => $this->postedBy ? [
                'id' => $this->postedBy->id,
                'name' => $this->postedBy->name,
            ] : null),
            'posted_at' => $this->posted_at?->toDateTimeString(),
            'items_count' => $this->whenCounted('items'),
            'items' => StockOpnameItemResource::collection($this->whenLoaded('items')),
            'movements' => InventoryMovementResource::collection($this->whenLoaded('movements')),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
