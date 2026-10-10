<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('unauthenticated user is redirected to login when accessing log viewer', function () {
    $response = $this->get('/log-viewer');

    $response->assertRedirect('/login');
});

test('user without owner role is forbidden from accessing log viewer', function () {
    Role::create(['name' => 'kasir', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole('kasir');

    $response = $this->actingAs($user)->get('/log-viewer');

    $response->assertForbidden();
});

test('user with owner role can access log viewer', function () {
    Role::create(['name' => 'owner', 'guard_name' => 'web']);
    $owner = User::factory()->create();
    $owner->assignRole('owner');

    $response = $this->actingAs($owner)->get('/log-viewer');

    $response->assertOk();
});
