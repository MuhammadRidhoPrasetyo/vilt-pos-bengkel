<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockTransferItem extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'stock_transfer_id',
        'product_variant_id',
        'from_warehouse_id',
        'from_warehouse_location_id',
        'to_warehouse_id',
        'to_warehouse_location_id',
        'quantity',
        'unit_cost',
        'product_price_id',
    ];

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
