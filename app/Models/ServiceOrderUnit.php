<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceOrderUnit extends Model
{
    use HasUuids;

    protected $fillable = [
        'service_order_id',
        'customer_vehicle_id',
        'status',
        'checkin_at',
        'completed_at',
        'plate_number',
        'brand',
        'model',
        'color',
        'complaint',
        'diagnosis',
        'work_done',
        'estimated_total',
    ];

    protected function casts(): array
    {
        return [
            'checkin_at' => 'datetime',
            'completed_at' => 'datetime',
            'estimated_total' => 'decimal:2',
        ];
    }

    public function serviceOrder(): BelongsTo
    {
        return $this->belongsTo(ServiceOrder::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(CustomerVehicle::class, 'customer_vehicle_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ServiceOrderItem::class);
    }
}
