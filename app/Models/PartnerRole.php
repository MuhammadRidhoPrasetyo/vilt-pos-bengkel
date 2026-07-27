<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PartnerRole extends BaseModel
{
    public function partners(): BelongsToMany
    {
        return $this->belongsToMany(Partner::class, 'partner_role_partner');
    }
}
