<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Partner extends BaseModel
{
    use SoftDeletes;

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class)->withTrashed();
    }

    public function linkedStore(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'linked_store_id')->withTrashed();
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
