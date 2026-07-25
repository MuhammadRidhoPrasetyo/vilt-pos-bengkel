<?php

namespace App\Repositories;

use App\Models\Attribute;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class AttributeRepository
{
    public function paginate(?string $search = null): LengthAwarePaginator
    {
        return Attribute::query()
            ->with(['store:id,name', 'options:id,attribute_id,value'])
            ->when($search, fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->latest()
            ->paginate(10)
            ->withQueryString();
    }

    public function options(): Collection
    {
        return Attribute::query()
            ->with('options:id,attribute_id,value')
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function create(array $data): Attribute
    {
        return Attribute::create($data);
    }

    public function update(Attribute $attribute, array $data): Attribute
    {
        $attribute->update($data);

        return $attribute->refresh();
    }

    public function delete(Attribute $attribute): void
    {
        $attribute->delete();
    }
}
