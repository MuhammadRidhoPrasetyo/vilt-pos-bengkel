<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_category_id' => $this->product_category_id,
            'brand_id' => $this->brand_id,
            'unit_id' => $this->unit_id,
            'name' => $this->name,
            'receipt_name' => $this->receipt_name,
            'display_receipt_name' => $this->display_receipt_name,
            'item_type' => $this->item_type,
            'has_variants' => $this->has_variants,
            'description' => $this->description,
            'images' => $this->relationLoaded('media') ? $this->getMedia('images')->map(fn ($media) => [
                'id' => $media->id,
                'url' => $media->getUrl(),
                'thumb_url' => $media->hasGeneratedConversion('thumb') ? $media->getUrl('thumb') : $media->getUrl(),
                'name' => $media->file_name,
                'size' => $media->size,
            ])->values() : [],
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category?->id,
                'name' => $this->category?->name,
            ]),
            'brand' => $this->whenLoaded('brand', fn () => [
                'id' => $this->brand?->id,
                'name' => $this->brand?->name,
            ]),
            'unit' => $this->whenLoaded('unit', fn () => [
                'id' => $this->unit?->id,
                'name' => $this->unit?->name,
            ]),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
