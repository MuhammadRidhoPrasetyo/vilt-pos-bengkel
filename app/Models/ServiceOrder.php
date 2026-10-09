<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceOrder extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'number',
        'store_id',
        'customer_id',
        'status',
        'checkin_at',
        'completed_at',
        'general_complaint',
        'estimated_total',
        'transaction_id',
        'customer_name',
        'customer_phone',
        'vehicle_id',
        'plate_number',
        'vehicle_brand',
        'vehicle_model',
        'year',
        'color',
        'odometer',
        'diagnosis',
    ];

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
