<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PartnerRole extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'description',
    ];

    public function partners(): BelongsToMany
    {
        return $this->belongsToMany(Partner::class, 'partner_role_partner');
    }
}
