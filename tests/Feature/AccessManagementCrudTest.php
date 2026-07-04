<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('authenticated users can manage permissions', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('permissions.store'), [
            'name' => 'users.view',
            'guard_name' => 'web',
        ])
        ->assertRedirect(route('permissions.index'));

    $permission = Permission::where('name', 'users.view')->firstOrFail();

    $this->actingAs($user)
        ->put(route('permissions.update', $permission), [
            'name' => 'users.manage',
            'guard_name' => 'web',
        ])
        ->assertRedirect(route('permissions.index'));

    expect($permission->refresh()->name)->toBe('users.manage');

    $this->actingAs($user)
        ->delete(route('permissions.destroy', $permission))
        ->assertRedirect(route('permissions.index'));

    expect(Permission::where('name', 'users.manage')->exists())->toBeFalse();
});

test('authenticated users can manage roles with permissions', function () {
    $user = User::factory()->create();
    $permission = Permission::create(['name' => 'users.view', 'guard_name' => 'web']);

    $this->actingAs($user)
        ->post(route('roles.store'), [
            'name' => 'admin',
            'guard_name' => 'web',
            'permissions' => [$permission->id],
        ])
        ->assertRedirect(route('roles.index'));

    $role = Role::where('name', 'admin')->firstOrFail();

    expect($role->hasPermissionTo($permission))->toBeTrue();

    $this->actingAs($user)
        ->put(route('roles.update', $role), [
            'name' => 'manager',
            'guard_name' => 'web',
            'permissions' => [],
        ])
        ->assertRedirect(route('roles.index'));

    expect($role->refresh()->name)->toBe('manager')
        ->and($role->hasPermissionTo($permission))->toBeFalse();
});

test('authenticated users can manage users with roles', function () {
    $admin = User::factory()->create();
    $role = Role::create(['name' => 'cashier', 'guard_name' => 'web']);

    $this->actingAs($admin)
        ->post(route('users.store'), [
            'name' => 'Cashier User',
            'email' => 'cashier@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'active' => true,
            'top_navigation' => false,
            'roles' => [$role->id],
        ])
        ->assertRedirect(route('users.index'));

    $user = User::where('email', 'cashier@example.com')->firstOrFail();

    expect($user->hasRole($role))->toBeTrue();

    $this->actingAs($admin)
        ->put(route('users.update', $user), [
            'name' => 'Updated Cashier',
            'email' => 'cashier@example.com',
            'password' => null,
            'password_confirmation' => null,
            'active' => false,
            'top_navigation' => true,
            'roles' => [],
        ])
        ->assertRedirect(route('users.index'));

    expect($user->refresh()->name)->toBe('Updated Cashier')
        ->and($user->active)->toBeFalse()
        ->and($user->hasRole($role))->toBeFalse();

    $this->actingAs($admin)
        ->delete(route('users.destroy', $user))
        ->assertRedirect(route('users.index'));

    expect(User::where('email', 'cashier@example.com')->exists())->toBeFalse();
});
