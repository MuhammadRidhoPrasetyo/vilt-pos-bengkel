<?php

namespace App\Services;

use App\Models\CashFlowCategory;
use App\Repositories\CashFlowCategoryRepository;

class CashFlowCategoryService
{
    public function __construct(private readonly CashFlowCategoryRepository $cashFlowCategories) {}

    public function create(array $data): CashFlowCategory
    {
        return $this->cashFlowCategories->create($data);
    }

    public function update(CashFlowCategory $cashFlowCategory, array $data): CashFlowCategory
    {
        return $this->cashFlowCategories->update($cashFlowCategory, $data);
    }

    public function delete(CashFlowCategory $cashFlowCategory): void
    {
        $this->cashFlowCategories->delete($cashFlowCategory);
    }
}
