<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryMovementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'warehouse' => $this->whenLoaded('warehouse', fn () => [
                'id' => $this->warehouse?->id,
                'name' => $this->warehouse?->name,
            ]),
            'product_variant' => $this->whenLoaded('productVariant', fn () => [
                'id' => $this->productVariant?->id,
                'name' => $this->productVariant?->display_receipt_name,
                'sku' => $this->productVariant?->sku,
            ]),
            'inventory_batch' => $this->whenLoaded('inventoryBatch', fn () => $this->inventoryBatch ? [
                'id' => $this->inventoryBatch->id,
                'unit_cost' => (float) $this->inventoryBatch->unit_cost,
                'received_at' => $this->inventoryBatch->received_at?->toDateString(),
            ] : null),
            'type' => $this->type,
            'quantity' => (int) $this->quantity,
            'balance_after' => (int) $this->balance_after,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
