<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute as EloquentAttribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Product extends Model implements HasMedia
{
    use HasUuids;
    use InteractsWithMedia;
    use SoftDeletes;

    protected $fillable = [
        'product_category_id',
        'brand_id',
        'unit_id',
        'name',
        'receipt_name',
        'item_type',
        'has_variants',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'has_variants' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

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

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function attributes(): HasMany
    {
        return $this->hasMany(Attribute::class);
    }

    public function displayReceiptName(): EloquentAttribute
    {
        return EloquentAttribute::make(
            get: fn () => ! empty($this->receipt_name) ? $this->receipt_name : $this->name,
        );
    }

    /**
     * Get unique hierarchical warehouse location paths for this product (across variants and stocks).
     *
     * @return array<int, string>
     */
    public function getWarehouseLocationsAttribute(): array
    {
        if (! $this->relationLoaded('variants')) {
            $this->loadMissing('variants.stocks.warehouseLocation');
        }

        return $this->variants
            ->flatMap(function ($variant) {
                return $variant->relationLoaded('stocks')
                    ? $variant->stocks
                    : $variant->stocks()->with('warehouseLocation')->get();
            })
            ->map(fn ($stock) => $stock->warehouseLocation?->full_path)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
