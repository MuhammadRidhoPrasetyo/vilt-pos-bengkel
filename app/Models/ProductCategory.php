<?php

namespace App\Models;

use App\Models\Traits\HasStoreOrGlobalScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductCategory extends Model
{
    use HasStoreOrGlobalScope, HasUuids;

    protected $fillable = [
        'store_id',
        'parent_id',
        'name',
        'pricing_mode',
        'income_cash_flow_category_id',
        'expense_cash_flow_category_id',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function incomeCashFlowCategory(): BelongsTo
    {
        return $this->belongsTo(CashFlowCategory::class, 'income_cash_flow_category_id');
    }

    public function expenseCashFlowCategory(): BelongsTo
    {
        return $this->belongsTo(CashFlowCategory::class, 'expense_cash_flow_category_id');
    }
}
