<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockTransferItem extends BaseModel
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_cost' => 'decimal:2',
        ];
    }

    public function stockTransfer(): BelongsTo
    {
        return $this->belongsTo(StockTransfer::class)->withTrashed();
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class)->withTrashed();
    }

    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id')->withTrashed();
    }

    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id')->withTrashed();
    }

    public function fromWarehouseLocation(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocation::class, 'from_warehouse_location_id')->withTrashed();
    }

    public function toWarehouseLocation(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocation::class, 'to_warehouse_location_id')->withTrashed();
    }

    public function productPrice(): BelongsTo
    {
        return $this->belongsTo(ProductPrice::class);
    }
}
