<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockTransferItemResource extends JsonResource
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
            'from_warehouse_id' => $this->from_warehouse_id,
            'from_warehouse' => $this->whenLoaded('fromWarehouse', fn () => ['id' => $this->fromWarehouse?->id, 'name' => $this->fromWarehouse?->name]),
            'from_warehouse_location_id' => $this->from_warehouse_location_id,
            'from_warehouse_location' => $this->whenLoaded('fromWarehouseLocation', fn () => $this->fromWarehouseLocation ? ['id' => $this->fromWarehouseLocation->id, 'name' => $this->fromWarehouseLocation->full_path ?? $this->fromWarehouseLocation->name] : null),
            'to_warehouse_id' => $this->to_warehouse_id,
            'to_warehouse' => $this->whenLoaded('toWarehouse', fn () => ['id' => $this->toWarehouse?->id, 'name' => $this->toWarehouse?->name]),
            'to_warehouse_location_id' => $this->to_warehouse_location_id,
            'to_warehouse_location' => $this->whenLoaded('toWarehouseLocation', fn () => $this->toWarehouseLocation ? ['id' => $this->toWarehouseLocation->id, 'name' => $this->toWarehouseLocation->full_path ?? $this->toWarehouseLocation->name] : null),
            'quantity' => (int) $this->quantity,
            'unit_cost' => (float) $this->unit_cost,
            'product_price_id' => $this->product_price_id,
        ];
    }
}
