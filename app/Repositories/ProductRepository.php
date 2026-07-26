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
            ->with(['category:id,name', 'brand:id,name', 'unit:id,name', 'media'])
            ->when($search, fn ($query) => $query->where('name', 'like', "%{$search}%"))
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
