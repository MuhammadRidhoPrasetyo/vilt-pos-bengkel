<?php

namespace App\Models;

use App\Models\Traits\HasStoreOrGlobalScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attribute extends Model
{
    use HasStoreOrGlobalScope, HasUuids;

    protected $fillable = [
        'store_id',
        'product_id',
        'name',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(AttributeOption::class);
    }
}
