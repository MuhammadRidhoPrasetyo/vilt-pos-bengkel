<?php

use App\Models\CashFlow;
use App\Models\CashFlowCategory;
use App\Models\Partner;
use App\Models\Store;
use App\Models\Transaction;
use App\Models\User;
use Database\Seeders\UserRolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(UserRolePermissionSeeder::class);

    $this->store = Store::create([
        'code' => 'STR-TEST',
        'name' => 'Store Test',
        'address' => 'Jl. Test No. 1',
    ]);

    $this->customer = Partner::create([
        'code' => 'CUST-001',
        'name' => 'Pelanggan Umum',
        'kind' => 'person',
    ]);
});

test('transaction summary total profit and grand total are restricted based on permissions', function () {
    $creator = User::factory()->create();

    // Create completed transaction with profit
    Transaction::create([
        'store_id' => $this->store->id,
        'customer_id' => $this->customer->id,
        'user_id' => $creator->id,
        'number' => 'TRX-TEST-001',
        'transaction_date' => now(),
        'type' => 'retail',
        'status' => 'completed',
        'payment_status' => 'paid',
        'subtotal' => 100000,
        'tax_amount' => 0,
        'discount_amount' => 0,
        'grand_total' => 100000,
        'paid_amount' => 100000,
        'change_amount' => 0,
        'total_cost' => 60000,
        'total_profit' => 40000,
    ]);

    // 1. Owner has all permissions: can see summary and profit
    $owner = User::factory()->create();
    $owner->assignRole('owner');

    $responseOwner = $this->actingAs($owner)->get('/transactions');
    $responseOwner->assertStatus(200);
    $responseOwner->assertInertia(fn ($page) => $page
        ->component('transactions/index')
        ->where('summary.total_grand_total', 100000)
        ->where('summary.total_profit', 40000)
    );

    // 2. Cashier role does NOT have transactions.summary.view or transactions.profit.view
    $kasir = User::factory()->create(['store_id' => $this->store->id]);
    $kasir->assignRole('kasir');

    $responseKasir = $this->actingAs($kasir)->get('/transactions');
    $responseKasir->assertStatus(200);
    $responseKasir->assertInertia(fn ($page) => $page
        ->component('transactions/index')
        ->where('summary.total_grand_total', null)
        ->where('summary.total_profit', null)
        ->where('summary.total_unpaid', null)
        ->where('summary.total_count', 1)
    );
});

test('dashboard finance and pos revenue widgets are restricted based on permissions', function () {
    $category = CashFlowCategory::create([
        'name' => 'Penjualan',
        'type' => 'income',
        'is_active' => true,
    ]);

    $creator = User::factory()->create();

    CashFlow::create([
        'store_id' => $this->store->id,
        'user_id' => $creator->id,
        'category_id' => $category->id,
        'amount' => 500000,
        'date' => now()->toDateString(),
        'type' => 'income',
        'description' => 'Pemasukan Kas',
    ]);

    Transaction::create([
        'store_id' => $this->store->id,
        'customer_id' => $this->customer->id,
        'user_id' => $creator->id,
        'number' => 'TRX-TEST-002',
        'transaction_date' => now(),
        'type' => 'retail',
        'status' => 'completed',
        'payment_status' => 'paid',
        'subtotal' => 250000,
        'tax_amount' => 0,
        'discount_amount' => 0,
        'grand_total' => 250000,
        'paid_amount' => 250000,
        'change_amount' => 0,
        'total_cost' => 150000,
        'total_profit' => 100000,
    ]);

    // 1. Owner can view finance and revenue
    $owner = User::factory()->create();
    $owner->assignRole('owner');

    $responseOwner = $this->actingAs($owner)->get('/dashboard');
    $responseOwner->assertStatus(200);
    $responseOwner->assertInertia(fn ($page) => $page
        ->component('Dashboard')
        ->where('summary.total_income', 500000)
        ->where('summary.net_balance', 500000)
        ->where('summary.total_revenue', 250000)
    );

    // 2. Cashier cannot view finance or revenue (returns 0 to protect sensitive numbers)
    $kasir = User::factory()->create(['store_id' => $this->store->id]);
    $kasir->assignRole('kasir');

    $responseKasir = $this->actingAs($kasir)->get('/dashboard');
    $responseKasir->assertStatus(200);
    $responseKasir->assertInertia(fn ($page) => $page
        ->component('Dashboard')
        ->where('summary.total_income', 0)
        ->where('summary.total_expense', 0)
        ->where('summary.net_balance', 0)
        ->where('summary.total_revenue', 0)
        ->where('summary.total_transactions', 1)
    );
});
