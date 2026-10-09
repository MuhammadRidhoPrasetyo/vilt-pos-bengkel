<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryBatch extends Model
{
    use HasUuids;

    protected $fillable = [
        'product_variant_id',
        'warehouse_id',
        'warehouse_location_id',
        'purchase_item_id',
        'initial_quantity',
        'current_quantity',
        'unit_cost',
        'received_at',
    ];

    protected function casts(): array
    {
        return [
            'initial_quantity' => 'integer',
            'current_quantity' => 'integer',
            'unit_cost' => 'decimal:2',
            'received_at' => 'datetime',
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

    public function purchaseItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseItem::class);
    }
}
