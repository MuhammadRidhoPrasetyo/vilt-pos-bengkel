<?php

namespace App\Repositories;

use App\Models\Store;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class StoreRepository
{
    public function paginate(?string $search = null): LengthAwarePaginator
    {
        return Store::query()
            ->when($search, fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%");
            }))
            ->latest()
            ->paginate(10)
            ->withQueryString();
    }

    public function options(): Collection
    {
        return Store::query()->select(['id', 'name'])->orderBy('name')->get();
    }

    public function create(array $data): Store
    {
        return Store::create($data);
    }

    public function update(Store $store, array $data): Store
    {
        $store->update($data);

        return $store->refresh();
    }

    public function delete(Store $store): void
    {
        $store->delete();
    }
}
