<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute as EloquentAttribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

    protected static function booted(): void
    {
        static::creating(function (ProductVariant $variant) {
            if (empty($variant->sku)) {
                $variant->sku = static::generateUniqueSku($variant);
            }
        });
    }

    public static function generateUniqueSku(ProductVariant $variant): string
    {
        $product = $variant->product;
        if (! $product && $variant->product_id) {
            $product = Product::with('category')->find($variant->product_id);
        }

        $categoryName = $product?->category?->name;
        $itemType = $product?->item_type ?? 'part';

        if ($itemType === 'labor') {
            $catCode = 'JSA';
        } elseif (! empty($categoryName)) {
            $cleanCat = strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $categoryName));
            $catCode = substr($cleanCat, 0, 3) ?: 'PRT';
        } else {
            $catCode = 'PRT';
        }

        $productName = $product?->name ?? 'VAR';
        $words = explode(' ', trim($productName));
        if (count($words) >= 2) {
            $prodCode = '';
            foreach ($words as $w) {
                $cleanW = preg_replace('/[^A-Za-z0-9]/', '', $w);
                if (! empty($cleanW)) {
                    $prodCode .= strtoupper($cleanW[0]);
                }
            }
            $prodCode = substr($prodCode, 0, 4);
        } else {
            $cleanProd = strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $productName));
            $prodCode = substr($cleanProd, 0, 4) ?: 'ITEM';
        }

        $basePrefix = "{$catCode}-{$prodCode}";

        $counter = 1;
        do {
            $candidateSku = sprintf('%s-%03d', $basePrefix, $counter);
            $exists = static::where('sku', $candidateSku)->exists();
            $counter++;
        } while ($exists);

        return $candidateSku;
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

    public function discounts(): HasMany
    {
        return $this->hasMany(ProductDiscount::class);
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(ProductStock::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }

    public function priceHistories(): HasMany
    {
        return $this->hasMany(ProductPriceHistory::class);
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
