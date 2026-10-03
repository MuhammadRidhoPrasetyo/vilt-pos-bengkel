<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CashFlow extends BaseModel
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'date' => 'date',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class)->withTrashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(CashFlowCategory::class, 'category_id');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
