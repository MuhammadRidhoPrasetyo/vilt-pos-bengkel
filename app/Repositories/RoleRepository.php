<?php

namespace App\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\Permission\Models\Role;

class RoleRepository
{
    public function paginate(?string $search = null): LengthAwarePaginator
    {
        return Role::query()
            ->with('permissions:id,name')
            ->when($search, fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->withCount(['permissions', 'users'])
            ->latest()
            ->paginate(10)
            ->withQueryString();
    }

    public function create(array $data): Role
    {
        return Role::create($data);
    }

    public function update(Role $role, array $data): Role
    {
        $role->update($data);

        return $role->refresh();
    }

    public function delete(Role $role): void
    {
        $role->delete();
    }
}
