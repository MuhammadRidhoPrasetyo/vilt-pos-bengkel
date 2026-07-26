<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryMovement extends BaseModel
{
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'balance_after' => 'integer',
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

    public function inventoryBatch(): BelongsTo
    {
        return $this->belongsTo(InventoryBatch::class);
    }
}
