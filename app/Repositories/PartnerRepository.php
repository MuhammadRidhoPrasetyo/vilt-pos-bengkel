<?php

namespace App\Repositories;

use App\Models\Partner;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PartnerRepository
{
    public function paginate(?string $search = null): LengthAwarePaginator
    {
        return Partner::query()
            ->with(['store:id,name', 'linkedStore:id,name', 'roles:id,name'])
            ->when($search, fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('contact_person', 'like', "%{$search}%");
            }))
            ->latest()
            ->paginate(10)
            ->withQueryString();
    }

    public function create(array $data): Partner
    {
        return Partner::create($data);
    }

    public function update(Partner $partner, array $data): Partner
    {
        $partner->update($data);

        return $partner->refresh();
    }

    public function delete(Partner $partner): void
    {
        $partner->delete();
    }
}
