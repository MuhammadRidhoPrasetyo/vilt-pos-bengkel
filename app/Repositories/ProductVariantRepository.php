<?php

namespace App\Repositories;

use App\Models\ProductVariant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProductVariantRepository
{
    public function paginate(?string $search = null): LengthAwarePaginator
    {
        return ProductVariant::query()
            ->with(['product:id,name', 'attributeOptions:id,attribute_id,value', 'attributeOptions.attribute:id,name'])
            ->when($search, function ($query) use ($search) {
                $query->where('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%")
                    ->orWhere('name_suffix', 'like', "%{$search}%")
                    ->orWhereHas('product', fn ($productQuery) => $productQuery->where('name', 'like', "%{$search}%"));
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();
    }

    public function create(array $data): ProductVariant
    {
        return ProductVariant::create($data);
    }

    public function update(ProductVariant $productVariant, array $data): ProductVariant
    {
        $productVariant->update($data);

        return $productVariant->refresh();
    }

    public function delete(ProductVariant $productVariant): void
    {
        $productVariant->delete();
    }
}
