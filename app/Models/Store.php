<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Store extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'phone',
        'email',
        'address',
        'city',
        'province',
        'postal_code',
        'receipt_number_format',
        'receipt_sequence',
        'receipt_sequence_year',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'receipt_sequence' => 'integer',
            'receipt_sequence_year' => 'integer',
        ];
    }
}
