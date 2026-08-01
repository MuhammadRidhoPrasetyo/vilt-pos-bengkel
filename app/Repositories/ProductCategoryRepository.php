<?php

namespace App\Repositories;

use App\Models\ProductCategory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ProductCategoryRepository
{
    public function paginate(?string $search = null): LengthAwarePaginator
    {
        return ProductCategory::query()
            ->with(['store:id,name', 'parent:id,name', 'incomeCashFlowCategory:id,name', 'expenseCashFlowCategory:id,name'])
            ->when($search, fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->latest()
            ->paginate(10)
            ->withQueryString();
    }

    public function options(): Collection
    {
        return ProductCategory::query()
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function create(array $data): ProductCategory
    {
        return ProductCategory::create($data);
    }

    public function update(ProductCategory $productCategory, array $data): ProductCategory
    {
        $productCategory->update($data);

        return $productCategory->refresh();
    }

    public function delete(ProductCategory $productCategory): void
    {
        $productCategory->delete();
    }
}
