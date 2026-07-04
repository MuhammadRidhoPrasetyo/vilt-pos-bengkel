<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\RoleResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly UserService $service
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('users/index', [
            'users' => UserResource::collection($this->users->paginate($request->string('search')->toString())),
            'filters' => [
                'search' => $request->string('search')->toString(),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('users/create', [
            'roles' => RoleResource::collection($this->users->roleOptions()),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->service->create($request->validated());

        return redirect()->route('users.index')->with('success', 'User berhasil dibuat.');
    }

    public function show(User $user): Response
    {
        return Inertia::render('users/show', [
            'user' => new UserResource($this->users->findWithRoles($user)),
        ]);
    }

    public function edit(User $user): Response
    {
        return Inertia::render('users/edit', [
            'user' => new UserResource($this->users->findWithRoles($user)),
            'roles' => RoleResource::collection($this->users->roleOptions()),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->service->update($user, $request->validated());

        return redirect()->route('users.index')->with('success', 'User berhasil diperbarui.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->service->delete($user, $request->user());

        return redirect()->route('users.index')->with('success', 'User berhasil dihapus.');
    }
}
