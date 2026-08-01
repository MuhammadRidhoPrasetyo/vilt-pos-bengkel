<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionItemBatchResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'transaction_item_id' => $this->transaction_item_id,
            'inventory_batch_id' => $this->inventory_batch_id,
            'inventory_batch' => $this->whenLoaded('inventoryBatch', fn () => [
                'id' => $this->inventoryBatch?->id,
                'initial_quantity' => (int) $this->inventoryBatch?->initial_quantity,
                'current_quantity' => (int) $this->inventoryBatch?->current_quantity,
                'unit_cost' => (float) $this->inventoryBatch?->unit_cost,
                'received_at' => $this->inventoryBatch?->received_at?->toDateTimeString(),
                'warehouse' => $this->inventoryBatch?->warehouse ? [
                    'id' => $this->inventoryBatch->warehouse->id,
                    'name' => $this->inventoryBatch->warehouse->name,
                    'code' => $this->inventoryBatch->warehouse->code,
                ] : null,
            ]),
            'quantity' => (int) $this->quantity,
            'unit_cost' => (float) $this->unit_cost,
            'total_cost' => (float) $this->quantity * (float) $this->unit_cost,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
