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
            'receipt_name' => $this->receipt_name,
            'display_receipt_name' => $this->display_receipt_name,
            'default_purchase_price' => $this->default_purchase_price,
            'default_selling_price' => $this->default_selling_price,
            'is_active' => $this->is_active,
            'images' => $this->relationLoaded('media') ? $this->getMedia('images')->map(fn ($media) => [
                'id' => $media->id,
                'url' => $media->getUrl(),
                'thumb_url' => $media->hasGeneratedConversion('thumb') ? $media->getUrl('thumb') : $media->getUrl(),
                'name' => $media->file_name,
                'size' => $media->size,
            ])->values() : [],
            'product' => $this->whenLoaded('product', fn () => [
                'id' => $this->product?->id,
                'name' => $this->product?->name,
                'receipt_name' => $this->product?->receipt_name,
                'images' => $this->product?->relationLoaded('media') ? $this->product->getMedia('images')->map(fn ($media) => [
                    'id' => $media->id,
                    'url' => $media->getUrl(),
                    'thumb_url' => $media->hasGeneratedConversion('thumb') ? $media->getUrl('thumb') : $media->getUrl(),
                    'name' => $media->file_name,
                    'size' => $media->size,
                ])->values() : [],
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
