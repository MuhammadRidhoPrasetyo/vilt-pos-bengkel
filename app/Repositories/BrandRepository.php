<?php

namespace App\Repositories;

use App\Models\Brand;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class BrandRepository
{
    public function paginate(?string $search = null): LengthAwarePaginator
    {
        return Brand::query()
            ->when($search, fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->latest()
            ->paginate(10)
            ->withQueryString();
    }

    public function create(array $data): Brand
    {
        return Brand::create($data);
    }

    public function update(Brand $brand, array $data): Brand
    {
        $brand->update($data);

        return $brand->refresh();
    }

    public function delete(Brand $brand): void
    {
        $brand->delete();
    }
}
