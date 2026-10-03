<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceOrderItem extends BaseModel
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'line_total' => 'decimal:2',
            'assigned_at' => 'datetime',
        ];
    }

    public function serviceOrder(): BelongsTo
    {
        return $this->belongsTo(ServiceOrder::class)->withTrashed();
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class)->withTrashed();
    }

    public function mechanic(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mechanic_id')->withTrashed();
    }
}
