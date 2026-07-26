<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductPrice extends BaseModel
{
    protected function casts(): array
    {
        return [
            'purchase_price' => 'decimal:2',
            'markup' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(ProductPriceHistory::class);
    }
}
