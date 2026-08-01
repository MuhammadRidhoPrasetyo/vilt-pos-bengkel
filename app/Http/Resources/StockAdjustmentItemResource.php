<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockAdjustmentItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_variant_id' => $this->product_variant_id,
            'product_variant' => $this->whenLoaded('productVariant', fn () => [
                'id' => $this->productVariant?->id,
                'name' => $this->productVariant?->display_receipt_name,
                'sku' => $this->productVariant?->sku,
            ]),
            'warehouse_id' => $this->warehouse_id,
            'warehouse' => $this->whenLoaded('warehouse', fn () => [
                'id' => $this->warehouse?->id,
                'name' => $this->warehouse?->name,
            ]),
            'warehouse_location_id' => $this->warehouse_location_id,
            'warehouse_location' => $this->whenLoaded('warehouseLocation', fn () => $this->warehouseLocation ? [
                'id' => $this->warehouseLocation->id,
                'name' => $this->warehouseLocation->full_path ?? $this->warehouseLocation->name,
            ] : null),
            'adjustment_type' => $this->adjustment_type,
            'quantity' => (int) $this->quantity,
            'unit_cost' => (float) $this->unit_cost,
            'note' => $this->note,
        ];
    }
}
