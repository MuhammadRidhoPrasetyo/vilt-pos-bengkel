<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Http\Resources\PermissionResource;
use App\Http\Resources\RoleResource;
use App\Repositories\PermissionRepository;
use App\Repositories\RoleRepository;
use App\Services\RoleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function __construct(
        private readonly RoleRepository $roles,
        private readonly PermissionRepository $permissions,
        private readonly RoleService $service
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('roles/index', [
            'roles' => RoleResource::collection($this->roles->paginate($request->string('search')->toString())),
            'permissions' => PermissionResource::collection($this->permissions->options()),
            'filters' => [
                'search' => $request->string('search')->toString(),
            ],
        ]);
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('roles.index');
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $this->service->create($request->validated());

        return redirect()->route('roles.index')->with('success', 'Role berhasil dibuat.');
    }

    public function show(Role $role): RedirectResponse
    {
        return redirect()->route('roles.index');
    }

    public function edit(Role $role): RedirectResponse
    {
        return redirect()->route('roles.index');
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $this->service->update($role, $request->validated());

        return redirect()->route('roles.index')->with('success', 'Role berhasil diperbarui.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        $this->service->delete($role);

        return redirect()->route('roles.index')->with('success', 'Role berhasil dihapus.');
    }
}
