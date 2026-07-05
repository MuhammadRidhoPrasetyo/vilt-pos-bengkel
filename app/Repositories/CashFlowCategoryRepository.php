<?php

namespace App\Repositories;

use App\Models\CashFlowCategory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CashFlowCategoryRepository
{
    public function paginate(?string $search = null): LengthAwarePaginator
    {
        return CashFlowCategory::query()
            ->when($search, fn ($query) => $query->where('name', 'like', "%{$search}%")->orWhere('description', 'like', "%{$search}%"))
            ->latest()
            ->paginate(10)
            ->withQueryString();
    }

    public function create(array $data): CashFlowCategory
    {
        return CashFlowCategory::create($data);
    }

    public function update(CashFlowCategory $cashFlowCategory, array $data): CashFlowCategory
    {
        $cashFlowCategory->update($data);

        return $cashFlowCategory->refresh();
    }

    public function delete(CashFlowCategory $cashFlowCategory): void
    {
        $cashFlowCategory->delete();
    }
}
