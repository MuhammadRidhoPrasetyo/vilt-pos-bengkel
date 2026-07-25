<?php

namespace App\Models\Traits;

use App\Models\Store;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasStoreOrGlobalScope
{
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function scopeForStore(Builder $query, ?string $storeId): Builder
    {
        return $query->where(function (Builder $q) use ($storeId) {
            $q->whereNull('store_id');
            if ($storeId) {
                $q->orWhere('store_id', $storeId);
            }
        });
    }
}
