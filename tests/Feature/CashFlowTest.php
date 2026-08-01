<?php

use App\Models\CashFlow;
use App\Models\CashFlowCategory;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('authenticated user can view cash flows index page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/cash-flows');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('cash-flows/index'));
});

test('authenticated user can create manual income cash flow entry', function () {
    $user = User::factory()->create();
    $store = Store::create(['code' => 'STR-01', 'name' => 'Store Test', 'address' => 'Jl. Test No. 1']);
    $category = CashFlowCategory::create([
        'name' => 'Modal Awal',
        'type' => 'income',
        'is_active' => true,
    ]);

    $response = $this->actingAs($user)->post('/cash-flows', [
        'store_id' => $store->id,
        'category_id' => $category->id,
        'amount' => 1500000,
        'date' => now()->toDateString(),
        'type' => 'income',
        'description' => 'Setoran modal operasional',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('cash_flows', [
        'store_id' => $store->id,
        'category_id' => $category->id,
        'amount' => 1500000,
        'type' => 'income',
        'description' => 'Setoran modal operasional',
    ]);
});

test('authenticated user can create manual expense cash flow entry', function () {
    $user = User::factory()->create();
    $store = Store::create(['code' => 'STR-01', 'name' => 'Store Test', 'address' => 'Jl. Test No. 1']);
    $category = CashFlowCategory::create([
        'name' => 'Pembayaran Listrik',
        'type' => 'expense',
        'is_active' => true,
    ]);

    $response = $this->actingAs($user)->post('/cash-flows', [
        'store_id' => $store->id,
        'category_id' => $category->id,
        'amount' => 350000,
        'date' => now()->toDateString(),
        'type' => 'expense',
        'description' => 'Tagihan listrik bulanan',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('cash_flows', [
        'store_id' => $store->id,
        'category_id' => $category->id,
        'amount' => 350000,
        'type' => 'expense',
        'description' => 'Tagihan listrik bulanan',
    ]);
});

test('manual cash flow entry can be deleted', function () {
    $user = User::factory()->create();
    $store = Store::create(['code' => 'STR-02', 'name' => 'Store Test 2', 'address' => 'Jl. Test No. 2']);
    $category = CashFlowCategory::create([
        'name' => 'Biaya Kebersihan',
        'type' => 'expense',
        'is_active' => true,
    ]);

    $cashFlow = CashFlow::create([
        'store_id' => $store->id,
        'user_id' => $user->id,
        'category_id' => $category->id,
        'amount' => 50000,
        'date' => now()->toDateString(),
        'type' => 'expense',
        'description' => 'Iuran sampah',
    ]);

    $response = $this->actingAs($user)->delete("/cash-flows/{$cashFlow->id}");

    $response->assertRedirect();
    $this->assertDatabaseMissing('cash_flows', [
        'id' => $cashFlow->id,
    ]);
});
