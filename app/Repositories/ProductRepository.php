<?php

namespace App\Repositories;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ProductRepository
{
    public function paginate(?string $search = null): LengthAwarePaginator
    {
        return Product::query()
            ->with([
                'category:id,name',
                'brand:id,name',
                'unit:id,name',
                'media',
                'variants.stocks.warehouseLocation',
            ])
            ->when($search, fn ($query) => $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('receipt_name', 'like', "%{$search}%")
                    ->orWhereHas('variants', fn ($vq) => $vq->where('sku', 'like', "%{$search}%")->orWhere('barcode', 'like', "%{$search}%"))
                    ->orWhereHas('variants.stocks.warehouseLocation', fn ($lq) => $lq->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"));
            }))
            ->latest()
            ->paginate(10)
            ->withQueryString();
    }

    public function options(): Collection
    {
        return Product::query()
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function create(array $data): Product
    {
        return Product::create($data);
    }

    public function update(Product $product, array $data): Product
    {
        $product->update($data);

        return $product->refresh();
    }

    public function delete(Product $product): void
    {
        $product->delete();
    }
}
