<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'name_suffix' => $this->name_suffix,
            'default_purchase_price' => $this->default_purchase_price,
            'default_selling_price' => $this->default_selling_price,
            'is_active' => $this->is_active,
            'product' => $this->whenLoaded('product', fn () => [
                'id' => $this->product?->id,
                'name' => $this->product?->name,
            ]),
            'attribute_option_ids' => $this->whenLoaded('attributeOptions', fn () => $this->attributeOptions->pluck('id')->values()),
            'attribute_option_labels' => $this->whenLoaded('attributeOptions', fn () => $this->attributeOptions->map(fn ($option) => trim(($option->attribute?->name ? $option->attribute->name.' : ' : '').$option->value))->values()),
            'attribute_options' => $this->whenLoaded('attributeOptions', fn () => $this->attributeOptions->map(fn ($option) => [
                'id' => $option->id,
                'name' => $option->attribute?->name,
                'value' => $option->value,
                'label' => trim(($option->attribute?->name ? $option->attribute->name.' : ' : '').$option->value),
            ])->values()),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
