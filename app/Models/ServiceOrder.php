<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceOrder extends BaseModel
{
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
        return $this->belongsTo(Store::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'customer_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(CustomerVehicle::class, 'vehicle_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ServiceOrderItem::class);
    }
}
