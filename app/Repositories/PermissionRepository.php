<?php

namespace App\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Permission;

class PermissionRepository
{
    public function paginate(?string $search = null): LengthAwarePaginator
    {
        return Permission::query()
            ->when($search, fn ($query) => $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            }))
            ->withCount('roles')
            ->latest()
            ->paginate(10)
            ->withQueryString();
    }

    public function create(array $data): Permission
    {
        return Permission::create($data);
    }

    public function update(Permission $permission, array $data): Permission
    {
        $permission->update($data);

        return $permission->refresh();
    }

    public function delete(Permission $permission): void
    {
        $permission->delete();
    }

    /**
     * @return Collection<int, Permission>
     */
    public function options(): Collection
    {
        return Permission::query()
            ->select(['id', 'name', 'guard_name', 'description'])
            ->orderBy('name')
            ->get();
    }
}
