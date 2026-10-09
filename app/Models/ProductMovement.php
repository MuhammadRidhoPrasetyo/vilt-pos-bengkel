<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ProductMovement extends Model
{
    use HasUuids;

    protected $fillable = [
        'product_id',
        'store_id',
        'movement_type',
        'quantity',
        'movementable_type',
        'movementable_id',
        'occurred_at',
        'created_by',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'quantity' => 'integer',
        ];
    }
}
