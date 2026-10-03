<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockOpname extends BaseModel
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'posted_at' => 'datetime',
            'total_system_qty' => 'integer',
            'total_physical_qty' => 'integer',
            'total_difference_qty' => 'integer',
            'total_difference_value' => 'decimal:2',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class)->withTrashed();
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class)->withTrashed();
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
        return $this->hasMany(StockOpnameItem::class);
    }

    public function movements(): MorphMany
    {
        return $this->morphMany(InventoryMovement::class, 'reference', 'reference_type', 'reference_id');
    }
}
