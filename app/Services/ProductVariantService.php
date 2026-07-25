<?php

namespace App\Services;

use App\Models\ProductVariant;
use App\Repositories\ProductVariantRepository;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ProductVariantService
{
    public function __construct(private readonly ProductVariantRepository $productVariants) {}

    public function create(array $data): ProductVariant
    {
        return DB::transaction(function () use ($data) {
            $productVariant = $this->productVariants->create(Arr::except($data, ['attribute_option_ids']));
            $productVariant->attributeOptions()->sync($data['attribute_option_ids'] ?? []);

            return $productVariant->load(['product:id,name', 'attributeOptions:id,attribute_id,value', 'attributeOptions.attribute:id,name']);
        });
    }

    public function update(ProductVariant $productVariant, array $data): ProductVariant
    {
        return DB::transaction(function () use ($productVariant, $data) {
            $productVariant = $this->productVariants->update($productVariant, Arr::except($data, ['attribute_option_ids']));
            $productVariant->attributeOptions()->sync($data['attribute_option_ids'] ?? []);

            return $productVariant->load(['product:id,name', 'attributeOptions:id,attribute_id,value', 'attributeOptions.attribute:id,name']);
        });
    }

    public function delete(ProductVariant $productVariant): void
    {
        $this->productVariants->delete($productVariant);
    }
}
