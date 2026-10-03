<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransactionPaymentAttempt extends BaseModel
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'amount_given' => 'decimal:2',
            'change' => 'decimal:2',
            'paid_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class)->withTrashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
