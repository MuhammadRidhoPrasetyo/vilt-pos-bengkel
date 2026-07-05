<?php

namespace App\Repositories;

use App\Models\DiscountType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DiscountTypeRepository
{
    public function paginate(?string $search = null): LengthAwarePaginator
    {
        return DiscountType::query()
            ->when($search, fn ($query) => $query->where('name', 'like', "%{$search}%")->orWhere('description', 'like', "%{$search}%"))
            ->latest()
            ->paginate(10)
            ->withQueryString();
    }

    public function create(array $data): DiscountType
    {
        return DiscountType::create($data);
    }

    public function update(DiscountType $discountType, array $data): DiscountType
    {
        $discountType->update($data);

        return $discountType->refresh();
    }

    public function delete(DiscountType $discountType): void
    {
        $discountType->delete();
    }
}
