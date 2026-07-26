<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductStock extends BaseModel
{
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'minimum_stock' => 'integer',
            'is_hidden' => 'boolean',
        ];
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function warehouseLocation(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocation::class);
    }
}
