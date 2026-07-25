<?php

namespace App\Repositories;

use App\Models\Unit;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class UnitRepository
{
    public function paginate(?string $search = null): LengthAwarePaginator
    {
        return Unit::query()
            ->with('store:id,name')
            ->when($search, fn ($query) => $query->where('name', 'like', "%{$search}%")->orWhere('symbol', 'like', "%{$search}%"))
            ->latest()
            ->paginate(10)
            ->withQueryString();
    }

    public function options(): Collection
    {
        return Unit::query()
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function create(array $data): Unit
    {
        return Unit::create($data);
    }

    public function update(Unit $unit, array $data): Unit
    {
        $unit->update($data);

        return $unit->refresh();
    }

    public function delete(Unit $unit): void
    {
        $unit->delete();
    }
}
