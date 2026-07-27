<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $unitPrice = (float) $this->unit_purchase_price;
        $qty = (int) $this->quantity_ordered;
        $subtotal = $unitPrice * $qty;
        $discountAmount = 0;

        if ($this->item_discount_type === 'percent') {
            $discountAmount = $subtotal * ((float) $this->item_discount_value / 100);
        } elseif ($this->item_discount_type === 'amount') {
            $discountAmount = (float) $this->item_discount_value;
        }

        $totalPrice = max(0, $subtotal - $discountAmount);

        return [
            'id' => $this->id,
            'purchase_id' => $this->purchase_id,
            'product_variant_id' => $this->product_variant_id,
            'product_variant' => $this->whenLoaded('productVariant', function () {
                return [
                    'id' => $this->productVariant->id,
                    'name' => $this->productVariant->name,
                    'sku' => $this->productVariant->sku,
                    'barcode' => $this->productVariant->barcode,
                    'product_name' => $this->productVariant->product?->name,
                ];
            }),
            'price_type' => $this->price_type,
            'quantity_ordered' => $qty,
            'unit_purchase_price' => $unitPrice,
            'item_discount_type' => $this->item_discount_type,
            'item_discount_value' => $this->item_discount_value ? (float) $this->item_discount_value : 0,
            'subtotal' => $subtotal,
            'discount_amount' => $discountAmount,
            'total_price' => $totalPrice,
            'inventory_batches' => $this->whenLoaded('inventoryBatches'),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
