<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        return $this->belongsToMany(PartnerRole::class, 'partner_role_partner');
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(CustomerVehicle::class, 'customer_id');
    }
}
