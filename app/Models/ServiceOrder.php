<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceOrder extends BaseModel
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'checkin_at' => 'datetime',
            'completed_at' => 'datetime',
            'estimated_total' => 'decimal:2',
            'year' => 'integer',
            'odometer' => 'integer',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class)->withTrashed();
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'customer_id')->withTrashed();
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(CustomerVehicle::class, 'vehicle_id')->withTrashed();
    }

    public function items(): HasMany
    {
        return $this->hasMany(ServiceOrderItem::class);
    }
}
