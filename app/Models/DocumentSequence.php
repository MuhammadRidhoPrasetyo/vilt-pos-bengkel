<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentSequence extends Model
{
    use HasUuids;

    protected $fillable = [
        'type',
        'store_id',
        'prefix',
        'format_pattern',
        'reset_frequency',
        'sequence',
        'day',
        'month',
        'year',
        'padding',
    ];

    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'day' => 'integer',
            'month' => 'integer',
            'year' => 'integer',
            'padding' => 'integer',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
