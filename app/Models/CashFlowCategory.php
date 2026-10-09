<?php

namespace App\Models;

use App\Models\Traits\HasStoreOrGlobalScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CashFlowCategory extends Model
{
    use HasStoreOrGlobalScope, HasUuids;

    protected $fillable = [
        'store_id',
        'name',
        'type',
        'description',
        'is_active',
        'is_system',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_system' => 'boolean',
        ];
    }
}
