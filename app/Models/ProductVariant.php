<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute as EloquentAttribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ProductVariant extends BaseModel implements HasMedia
{
    use InteractsWithMedia;

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->fit(Fit::Contain, 300, 300)
            ->nonQueued();

        $this->addMediaConversion('medium')
            ->fit(Fit::Contain, 800, 800)
            ->nonQueued();
    }

    protected function casts(): array
    {
        return [
            'default_purchase_price' => 'decimal:2',
            'default_selling_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function attributeOptions(): BelongsToMany
    {
        return $this->belongsToMany(AttributeOption::class, 'product_variant_attributes');
    }

    public function displayReceiptName(): EloquentAttribute
    {
        return EloquentAttribute::make(
            get: function () {
                if (! empty($this->receipt_name)) {
                    return $this->receipt_name;
                }

                if ($this->relationLoaded('product') && $this->product && ! empty($this->product->receipt_name)) {
                    return trim($this->product->receipt_name.($this->name_suffix ? ' '.$this->name_suffix : ''));
                }

                $productName = $this->product?->name ?? '';

                return trim($productName.($this->name_suffix ? ' '.$this->name_suffix : ''));
            },
        );
    }
}
