<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Attribute extends BaseModel
{
    public function options(): HasMany
    {
        return $this->hasMany(AttributeOption::class);
    }
}
