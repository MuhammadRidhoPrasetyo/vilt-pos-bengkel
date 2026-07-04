<?php

namespace App\Services;

use App\Repositories\PermissionRepository;
use Illuminate\Support\Arr;
use Spatie\Permission\Models\Permission;

class PermissionService
{
    public function __construct(private readonly PermissionRepository $permissions) {}

    public function create(array $data): Permission
    {
        return $this->permissions->create(Arr::only($data, ['name', 'guard_name']));
    }

    public function update(Permission $permission, array $data): Permission
    {
        return $this->permissions->update($permission, Arr::only($data, ['name', 'guard_name']));
    }

    public function delete(Permission $permission): void
    {
        $this->permissions->delete($permission);
    }
}
