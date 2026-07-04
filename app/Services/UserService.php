<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UserService
{
    public function __construct(private readonly UserRepository $users) {}

    public function create(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = $this->users->create(Arr::except($data, ['roles', 'password_confirmation']));
            $user->syncRoles($data['roles'] ?? []);

            return $user->load('roles:id,name');
        });
    }

    public function update(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            if (blank($data['password'] ?? null)) {
                unset($data['password']);
            }

            $user = $this->users->update($user, Arr::except($data, ['roles', 'password_confirmation']));
            $user->syncRoles($data['roles'] ?? []);

            return $user->load('roles:id,name');
        });
    }

    public function delete(User $user, User $authenticatedUser): void
    {
        if ($user->is($authenticatedUser)) {
            throw ValidationException::withMessages([
                'user' => 'Akun yang sedang digunakan tidak dapat dihapus.',
            ]);
        }

        $this->users->delete($user);
    }
}
