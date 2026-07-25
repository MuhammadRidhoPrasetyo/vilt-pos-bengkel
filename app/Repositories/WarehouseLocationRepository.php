<?php

namespace App\Repositories;

use App\Models\WarehouseLocation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class WarehouseLocationRepository
{
    public function paginate(?string $search = null): LengthAwarePaginator
    {
        return WarehouseLocation::query()
            ->with(['warehouse:id,name', 'parent:id,name'])
            ->when($search, fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('type', 'like', "%{$search}%")
                    ->orWhereHas('warehouse', fn ($warehouseQuery) => $warehouseQuery->where('name', 'like', "%{$search}%"));
            }))
            ->latest()
            ->paginate(10)
            ->withQueryString();
    }

    public function options(): Collection
    {
        return WarehouseLocation::query()->select(['id', 'name'])->orderBy('name')->get();
    }

    public function create(array $data): WarehouseLocation
    {
        return WarehouseLocation::create($data);
    }

    public function update(WarehouseLocation $warehouseLocation, array $data): WarehouseLocation
    {
        $warehouseLocation->update($data);

        return $warehouseLocation->refresh();
    }

    public function delete(WarehouseLocation $warehouseLocation): void
    {
        $warehouseLocation->delete();
    }
}
