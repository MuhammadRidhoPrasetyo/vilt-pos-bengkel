<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerVehicle extends BaseModel
{
    protected $fillable = [
        'customer_id',
        'plate_number',
        'brand',
        'model',
        'year',
        'color',
        'notes',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'customer_id');
    }
}
