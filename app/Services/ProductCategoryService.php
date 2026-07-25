<?php

namespace App\Services;

use App\Models\ProductCategory;
use App\Repositories\ProductCategoryRepository;

class ProductCategoryService
{
    public function __construct(private readonly ProductCategoryRepository $productCategories) {}

    public function create(array $data): ProductCategory
    {
        return $this->productCategories->create($data);
    }

    public function update(ProductCategory $productCategory, array $data): ProductCategory
    {
        return $this->productCategories->update($productCategory, $data);
    }

    public function delete(ProductCategory $productCategory): void
    {
        $this->productCategories->delete($productCategory);
    }
}
