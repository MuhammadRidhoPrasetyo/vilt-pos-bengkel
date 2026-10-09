<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseItem extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'purchase_id',
        'product_variant_id',
        'price_type',
        'quantity_ordered',
        'unit_purchase_price',
        'item_discount_type',
        'item_discount_value',
    ];

    protected function casts(): array
    {
        return [
            'quantity_ordered' => 'integer',
            'unit_purchase_price' => 'decimal:2',
            'item_discount_value' => 'decimal:2',
        ];
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class)->withTrashed();
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class)->withTrashed();
    }

    public function inventoryBatches(): HasMany
    {
        return $this->hasMany(InventoryBatch::class);
    }
}
