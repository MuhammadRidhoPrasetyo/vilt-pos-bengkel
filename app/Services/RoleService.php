<?php

namespace App\Services;

use App\Repositories\RoleRepository;
use Illuminate\Support\Arr;
use Spatie\Permission\Models\Role;

class RoleService
{
    public function __construct(private readonly RoleRepository $roles) {}

    public function create(array $data): Role
    {
        $role = $this->roles->create(Arr::only($data, ['name', 'guard_name']));
        $role->syncPermissions($data['permissions'] ?? []);

        return $role->load('permissions:id,name');
    }

    public function update(Role $role, array $data): Role
    {
        $role = $this->roles->update($role, Arr::only($data, ['name', 'guard_name']));
        $role->syncPermissions($data['permissions'] ?? []);

        return $role->load('permissions:id,name');
    }

    public function delete(Role $role): void
    {
        $this->roles->delete($role);
    }
}
