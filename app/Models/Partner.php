<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Partner extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'store_id',
        'linked_store_id',
        'code',
        'name',
        'kind',
        'contact_person',
        'phone',
        'email',
        'address',
        'city',
        'province',
        'postal_code',
        'npwp',
        'bank_name',
        'bank_account',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

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
