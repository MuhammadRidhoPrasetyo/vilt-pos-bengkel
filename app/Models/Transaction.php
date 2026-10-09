<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'number',
        'store_id',
        'user_id',
        'customer_id',
        'payment_id',
        'service_order_id',
        'transaction_date',
        'type',
        'subtotal',
        'item_discount_total',
        'subtotal_after_item_discount',
        'universal_discount_mode',
        'universal_discount_value',
        'universal_discount_amount',
        'tax_rate',
        'tax_total',
        'grand_total',
        'paid_amount',
        'change_amount',
        'payment_status',
        'total_cost',
        'total_profit',
        'status',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'datetime',
            'subtotal' => 'decimal:2',
            'item_discount_total' => 'decimal:2',
            'subtotal_after_item_discount' => 'decimal:2',
            'universal_discount_value' => 'decimal:2',
            'universal_discount_amount' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'change_amount' => 'decimal:2',
            'total_cost' => 'decimal:2',
            'total_profit' => 'decimal:2',
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

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'customer_id')->withTrashed();
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function serviceOrder(): BelongsTo
    {
        return $this->belongsTo(ServiceOrder::class)->withTrashed();
    }

    public function items(): HasMany
    {
        return $this->hasMany(TransactionItem::class);
    }

    public function paymentAttempts(): HasMany
    {
        return $this->hasMany(TransactionPaymentAttempt::class);
    }
}
