<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockOpnameItem extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'stock_opname_id',
        'product_variant_id',
        'warehouse_location_id',
        'system_quantity',
        'physical_quantity',
        'difference_quantity',
        'unit_cost',
        'difference_value',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'system_quantity' => 'integer',
            'physical_quantity' => 'integer',
            'difference_quantity' => 'integer',
            'unit_cost' => 'decimal:2',
            'difference_value' => 'decimal:2',
        ];
    }

    public function stockOpname(): BelongsTo
    {
        return $this->belongsTo(StockOpname::class)->withTrashed();
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class)->withTrashed();
    }

    public function warehouseLocation(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocation::class)->withTrashed();
    }
}
