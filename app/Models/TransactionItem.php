<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransactionItem extends BaseModel
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'item_discount_value' => 'decimal:2',
            'item_discount_amount' => 'decimal:2',
            'final_unit_price' => 'decimal:2',
            'line_subtotal' => 'decimal:2',
            'line_total' => 'decimal:2',
            'unit_cost' => 'decimal:2',
            'line_cost_total' => 'decimal:2',
            'line_profit' => 'decimal:2',
            'price_edited' => 'boolean',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class)->withTrashed();
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class)->withTrashed();
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class)->withTrashed();
    }

    public function productStock(): BelongsTo
    {
        return $this->belongsTo(ProductStock::class);
    }

    public function discountType(): BelongsTo
    {
        return $this->belongsTo(DiscountType::class);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(TransactionItemBatch::class);
    }
}
