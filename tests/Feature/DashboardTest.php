<?php

use App\Models\CashFlow;
use App\Models\CashFlowCategory;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('authenticated user can view dashboard page with cash flows index data', function () {
    $user = User::factory()->create();
    $store = Store::create(['code' => 'STR-DASH', 'name' => 'Store Dashboard', 'address' => 'Jl. Dashboard']);
    $category = CashFlowCategory::create([
        'name' => 'Penjualan',
        'type' => 'income',
        'is_active' => true,
    ]);

    CashFlow::create([
        'store_id' => $store->id,
        'user_id' => $user->id,
        'category_id' => $category->id,
        'amount' => 500000,
        'date' => now()->toDateString(),
        'type' => 'income',
        'description' => 'Pendapatan Harian',
    ]);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Dashboard')
        ->has('summary')
        ->has('recentCashFlows')
        ->has('options')
    );
});
