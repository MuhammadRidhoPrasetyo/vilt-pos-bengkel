<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePermissionRequest;
use App\Http\Requests\UpdatePermissionRequest;
use App\Http\Resources\PermissionResource;
use App\Repositories\PermissionRepository;
use App\Services\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function __construct(
        private readonly PermissionRepository $permissions,
        private readonly PermissionService $service
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('permissions/index', [
            'permissions' => PermissionResource::collection($this->permissions->paginate($request->string('search')->toString())),
            'filters' => [
                'search' => $request->string('search')->toString(),
            ],
        ]);
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('permissions.index');
    }

    public function store(StorePermissionRequest $request): RedirectResponse
    {
        $this->service->create($request->validated());

        return redirect()->route('permissions.index')->with('success', 'Permission berhasil dibuat.');
    }

    public function show(Permission $permission): RedirectResponse
    {
        return redirect()->route('permissions.index');
    }

    public function edit(Permission $permission): RedirectResponse
    {
        return redirect()->route('permissions.index');
    }

    public function update(UpdatePermissionRequest $request, Permission $permission): RedirectResponse
    {
        $this->service->update($permission, $request->validated());

        return redirect()->route('permissions.index')->with('success', 'Permission berhasil diperbarui.');
    }

    public function destroy(Permission $permission): RedirectResponse
    {
        $this->service->delete($permission);

        return redirect()->route('permissions.index')->with('success', 'Permission berhasil dihapus.');
    }
}
