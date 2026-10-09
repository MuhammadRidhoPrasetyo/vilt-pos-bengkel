<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerVehicle extends Model
{
    use HasUuids, SoftDeletes;

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
        return $this->belongsTo(Partner::class, 'customer_id')->withTrashed();
    }
}
