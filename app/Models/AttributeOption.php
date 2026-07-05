<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttributeOption extends BaseModel
{
    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }
}
