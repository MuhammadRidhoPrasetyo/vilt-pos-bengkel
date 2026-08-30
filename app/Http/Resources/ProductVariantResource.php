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
            'name' => $this->display_receipt_name,
            'name_suffix' => $this->name_suffix,
            'receipt_name' => $this->receipt_name,
            'display_receipt_name' => $this->display_receipt_name,
            'default_purchase_price' => $this->default_purchase_price,
            'default_selling_price' => $this->default_selling_price,
            'price' => (float) ($this->default_selling_price ?? 0),
            'is_active' => $this->is_active,
            'image_url' => $this->getFirstMediaUrl('images', 'thumb')
                ?: ($this->product?->getFirstMediaUrl('images', 'thumb') ?: null),
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
                'item_type' => $this->product?->item_type,
                'category_id' => $this->product?->product_category_id,
                'category_name' => $this->product?->relationLoaded('category') ? $this->product->category?->name : null,
                'brand_name' => $this->product?->relationLoaded('brand') ? $this->product->brand?->name : null,
                'unit_name' => $this->product?->relationLoaded('unit') ? $this->product->unit?->name : null,
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
            'discounts' => $this->whenLoaded('discounts', fn () => $this->discounts->map(fn ($discount) => [
                'id' => $discount->id,
                'product_variant_id' => $discount->product_variant_id,
                'store_id' => $discount->store_id,
                'store_name' => $discount->store?->name ?? 'Semua Toko (Global)',
                'discount_type_id' => $discount->discount_type_id,
                'discount_type_name' => $discount->discountType?->name ?? '-',
                'type' => $discount->type,
                'type_label' => $discount->type === 'percent' ? 'Persentase (%)' : 'Nominal (Rp)',
                'value' => (float) $discount->value,
                'formatted_value' => $discount->type === 'percent' ? $discount->value.'%' : 'Rp '.number_format($discount->value, 0, ',', '.'),
                'created_at' => $discount->created_at?->toDateTimeString(),
            ])->values()),
            'stocks' => $this->whenLoaded('stocks', fn () => $this->stocks->map(fn ($stock) => [
                'id' => $stock->id,
                'product_variant_id' => $stock->product_variant_id,
                'store_id' => $stock->warehouse?->store_id,
                'warehouse_id' => $stock->warehouse_id,
                'warehouse_name' => $stock->warehouse?->name ?? '-',
                'warehouse_location_id' => $stock->warehouse_location_id,
                'warehouse_location_name' => $stock->warehouseLocation?->full_path ?? $stock->warehouseLocation?->name ?? '-',
                'quantity' => (int) $stock->quantity,
                'minimum_stock' => (int) $stock->minimum_stock,
                'is_hidden' => (bool) $stock->is_hidden,
                'created_at' => $stock->created_at?->toDateTimeString(),
            ])->values()),
            'prices' => $this->whenLoaded('prices', fn () => $this->prices->map(fn ($price) => [
                'id' => $price->id,
                'product_variant_id' => $price->product_variant_id,
                'store_id' => $price->store_id,
                'store_name' => $price->store?->name ?? 'Semua Toko (Global)',
                'price_type' => $price->price_type,
                'purchase_price' => (float) $price->purchase_price,
                'markup' => (float) $price->markup,
                'markup_type' => $price->markup_type,
                'selling_price' => (float) $price->selling_price,
                'is_active' => (bool) $price->is_active,
                'created_at' => $price->created_at?->toDateTimeString(),
            ])->values()),
            'price_histories' => $this->whenLoaded('priceHistories', fn () => $this->priceHistories->map(fn ($history) => [
                'id' => $history->id,
                'product_variant_id' => $history->product_variant_id,
                'store_id' => $history->store_id,
                'store_name' => $history->store?->name ?? 'Semua Toko (Global)',
                'product_price_id' => $history->product_price_id,
                'purchase_price' => (float) $history->purchase_price,
                'selling_price' => (float) $history->selling_price,
                'date' => $history->date?->toDateTimeString() ?? $history->created_at?->toDateTimeString(),
            ])->values()),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
