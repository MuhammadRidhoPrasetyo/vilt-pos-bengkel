<?php

namespace App\Repositories;

use App\Models\PartnerRole;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class PartnerRoleRepository
{
    public function paginate(?string $search = null): LengthAwarePaginator
    {
        return PartnerRole::query()
            ->when($search, fn ($query) => $query->where('name', 'like', "%{$search}%")->orWhere('description', 'like', "%{$search}%"))
            ->latest()
            ->paginate(10)
            ->withQueryString();
    }

    public function options(): Collection
    {
        return PartnerRole::query()->select(['id', 'name'])->orderBy('name')->get();
    }

    public function create(array $data): PartnerRole
    {
        return PartnerRole::create($data);
    }

    public function update(PartnerRole $partnerRole, array $data): PartnerRole
    {
        $partnerRole->update($data);

        return $partnerRole->refresh();
    }

    public function delete(PartnerRole $partnerRole): void
    {
        $partnerRole->delete();
    }
}
