<?php

namespace App\Models;

use App\Models\Traits\HasStoreOrGlobalScope;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attribute extends BaseModel
{
    use HasStoreOrGlobalScope;

    public function options(): HasMany
    {
        return $this->hasMany(AttributeOption::class);
    }
}
