<?php

namespace App\Models;

use App\Models\Traits\HasStoreOrGlobalScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasStoreOrGlobalScope, HasUuids;

    protected $fillable = [
        'store_id',
        'name',
        'type',
        'account_number',
        'account_name',
        'provider_code',
    ];
}
