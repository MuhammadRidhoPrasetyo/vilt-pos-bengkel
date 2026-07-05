<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Partner extends BaseModel
{
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function linkedStore(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'linked_store_id');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(PartnerRole::class);
    }
}
