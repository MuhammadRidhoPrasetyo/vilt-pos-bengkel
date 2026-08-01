<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('unauthenticated user accessing root is redirected to login', function () {
    $response = $this->get('/');

    $response->assertRedirect('/login');
});

test('authenticated user accessing root is redirected to home portal', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/');

    $response->assertRedirect('/home');
});

test('authenticated user can view portal page on /home', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/home');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('Portal'));
});

test('authenticated admin user can access dashboard page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('Dashboard'));
});

test('user logging in is redirected to home portal', function () {
    $user = User::factory()->create([
        'password' => bcrypt('password'),
    ]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect('/home');
});

test('user logging in after attempting dashboard is still redirected to home portal', function () {
    $user = User::factory()->create([
        'password' => bcrypt('password'),
    ]);

    $this->get('/dashboard')->assertRedirect('/login');

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect('/home');
});
