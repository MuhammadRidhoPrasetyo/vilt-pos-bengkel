<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Purchase extends BaseModel
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'discount_value' => 'decimal:2',
            'price' => 'decimal:2',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class)->withTrashed();
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'supplier_id')->withTrashed();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by')->withTrashed();
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function cashFlows(): MorphMany
    {
        return $this->morphMany(CashFlow::class, 'reference');
    }
}
