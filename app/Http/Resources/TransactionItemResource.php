<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'transaction_id' => $this->transaction_id,
            'item_type' => $this->item_type,
            'product_variant_id' => $this->product_variant_id,
            'product_variant' => new ProductVariantResource($this->whenLoaded('productVariant')),
            'description' => $this->description,
            'store_id' => $this->store_id,
            'product_stock_id' => $this->product_stock_id,
            'quantity' => (int) $this->quantity,
            'unit_price' => (float) $this->unit_price,
            'item_discount_mode' => $this->item_discount_mode,
            'item_discount_value' => (float) $this->item_discount_value,
            'item_discount_amount' => (float) $this->item_discount_amount,
            'final_unit_price' => (float) $this->final_unit_price,
            'line_subtotal' => (float) $this->line_subtotal,
            'line_total' => (float) $this->line_total,
            'unit_cost' => (float) $this->unit_cost,
            'line_cost_total' => (float) $this->line_cost_total,
            'line_profit' => (float) $this->line_profit,
            'price_edited' => (bool) $this->price_edited,
            'batches' => TransactionItemBatchResource::collection($this->whenLoaded('batches')),
        ];
    }
}
