<?php

use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('unauthenticated user is redirected to login when visiting profile', function () {
    $response = $this->get('/profile');

    $response->assertRedirect('/login');
});

test('authenticated user can view profile page with roles and store info', function () {
    $role = Role::create(['name' => 'Kasir', 'guard_name' => 'web']);
    $store = Store::create([
        'code' => 'TKO-01',
        'name' => 'Bengkel Pusat',
    ]);

    $user = User::factory()->create([
        'store_id' => $store->id,
        'nik' => '1234567890',
        'phone' => '08123456789',
        'address' => 'Jl. Merdeka No. 1',
    ]);
    $user->assignRole($role);

    $response = $this->actingAs($user)->get('/profile');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('profile/edit')
        ->where('user.name', $user->name)
        ->where('user.email', $user->email)
        ->where('user.nik', '1234567890')
        ->where('user.phone', '08123456789')
        ->where('user.address', 'Jl. Merdeka No. 1')
        ->where('user.store.name', 'Bengkel Pusat')
        ->where('user.roles.0.name', 'Kasir')
    );
});

test('user can update profile personal information', function () {
    $user = User::factory()->create([
        'name' => 'Nama Lama',
        'email' => 'lama@example.com',
        'phone' => '0811111111',
        'address' => 'Alamat Lama',
    ]);

    $response = $this->actingAs($user)->put('/profile', [
        'name' => 'Nama Baru',
        'email' => 'baru@example.com',
        'phone' => '0822222222',
        'address' => 'Alamat Baru',
    ]);

    $response->assertSessionHas('success', 'Informasi profil berhasil diperbarui.');

    $user->refresh();
    expect($user->name)->toBe('Nama Baru');
    expect($user->email)->toBe('baru@example.com');
    expect($user->phone)->toBe('0822222222');
    expect($user->address)->toBe('Alamat Baru');
});

test('profile update validates required fields and unique email', function () {
    $existingUser = User::factory()->create([
        'email' => 'used@example.com',
    ]);

    $user = User::factory()->create([
        'email' => 'me@example.com',
    ]);

    $response = $this->actingAs($user)->put('/profile', [
        'name' => '',
        'email' => 'used@example.com',
    ]);

    $response->assertSessionHasErrors(['name', 'email']);
});

test('user can update their password with correct current password', function () {
    $user = User::factory()->create([
        'password' => Hash::make('old-password-123'),
    ]);

    $response = $this->actingAs($user)->put('/profile/password', [
        'current_password' => 'old-password-123',
        'password' => 'new-secure-password-456',
        'password_confirmation' => 'new-secure-password-456',
    ]);

    $response->assertSessionHas('success', 'Password berhasil diperbarui.');

    $user->refresh();
    expect(Hash::check('new-secure-password-456', $user->password))->toBeTrue();
});

test('user cannot update password with incorrect current password', function () {
    $user = User::factory()->create([
        'password' => Hash::make('old-password-123'),
    ]);

    $response = $this->actingAs($user)->put('/profile/password', [
        'current_password' => 'wrong-current-password',
        'password' => 'new-secure-password-456',
        'password_confirmation' => 'new-secure-password-456',
    ]);

    $response->assertSessionHasErrors(['current_password']);

    $user->refresh();
    expect(Hash::check('old-password-123', $user->password))->toBeTrue();
});

test('user cannot update password if confirmation does not match', function () {
    $user = User::factory()->create([
        'password' => Hash::make('old-password-123'),
    ]);

    $response = $this->actingAs($user)->put('/profile/password', [
        'current_password' => 'old-password-123',
        'password' => 'new-secure-password-456',
        'password_confirmation' => 'mismatched-password-789',
    ]);

    $response->assertSessionHasErrors(['password']);

    $user->refresh();
    expect(Hash::check('old-password-123', $user->password))->toBeTrue();
});
