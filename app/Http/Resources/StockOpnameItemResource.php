<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockOpnameItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_variant_id' => $this->product_variant_id,
            'product_variant' => $this->whenLoaded('productVariant', fn () => [
                'id' => $this->productVariant?->id,
                'name' => $this->productVariant?->display_receipt_name ?? $this->productVariant?->name,
                'sku' => $this->productVariant?->sku,
                'unit_name' => $this->productVariant?->unit_name ?? 'Pcs',
            ]),
            'warehouse_location_id' => $this->warehouse_location_id,
            'warehouse_location' => $this->whenLoaded('warehouseLocation', fn () => $this->warehouseLocation ? [
                'id' => $this->warehouseLocation->id,
                'name' => $this->warehouseLocation->full_path ?? $this->warehouseLocation->name,
            ] : null),
            'system_quantity' => (int) $this->system_quantity,
            'physical_quantity' => (int) $this->physical_quantity,
            'difference_quantity' => (int) $this->difference_quantity,
            'unit_cost' => (float) $this->unit_cost,
            'difference_value' => (float) $this->difference_value,
            'note' => $this->note,
        ];
    }
}
