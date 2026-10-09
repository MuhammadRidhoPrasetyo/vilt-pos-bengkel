<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransactionItem extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'transaction_id',
        'product_variant_id',
        'store_id',
        'product_stock_id',
        'discount_type_id',
        'quantity',
        'unit_price',
        'item_discount_mode',
        'item_discount_value',
        'item_discount_amount',
        'final_unit_price',
        'line_subtotal',
        'line_total',
        'unit_cost',
        'line_cost_total',
        'line_profit',
        'price_edited',
        'pricing_mode',
        'item_type',
        'description',
    ];

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
