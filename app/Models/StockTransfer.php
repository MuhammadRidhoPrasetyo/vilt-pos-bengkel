<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockTransfer extends BaseModel
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'posted_at' => 'datetime',
        ];
    }

    public function fromStore(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'from_store_id')->withTrashed();
    }

    public function toStore(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'to_store_id')->withTrashed();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by')->withTrashed();
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockTransferItem::class);
    }

    public function movements(): MorphMany
    {
        return $this->morphMany(InventoryMovement::class, 'reference', 'reference_type', 'reference_id');
    }
}
