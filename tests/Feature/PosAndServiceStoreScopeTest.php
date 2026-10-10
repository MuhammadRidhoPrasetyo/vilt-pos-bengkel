<?php

use App\Models\Partner;
use App\Models\ServiceOrder;
use App\Models\Store;
use App\Models\Transaction;
use App\Models\User;
use Database\Seeders\UserRolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(UserRolePermissionSeeder::class);

    $this->storeA = Store::create([
        'code' => 'STR-A',
        'name' => 'Bengkel Cabang A',
        'address' => 'Jl. Cabang A No. 1',
    ]);

    $this->storeB = Store::create([
        'code' => 'STR-B',
        'name' => 'Bengkel Cabang B',
        'address' => 'Jl. Cabang B No. 2',
    ]);

    $this->customer = Partner::create([
        'code' => 'CUST-001',
        'name' => 'Pelanggan Umum',
        'kind' => 'person',
    ]);
});

test('owner can view and filter all stores on pos transactions and services', function () {
    $owner = User::factory()->create();
    $owner->assignRole('owner');

    // 1. Transactions page
    $responseTrx = $this->actingAs($owner)->get('/transactions');
    $responseTrx->assertOk();
    $responseTrx->assertInertia(fn ($page) => $page
        ->component('transactions/index')
        ->where('canFilterStore', true)
        ->has('options.stores', 2)
    );

    // 2. Services page
    $responseSvc = $this->actingAs($owner)->get('/services');
    $responseSvc->assertOk();
    $responseSvc->assertInertia(fn ($page) => $page
        ->component('services/index')
        ->where('canFilterStore', true)
        ->has('options.stores', 2)
    );
});

test('non owner user can only view their assigned store on pos transactions', function () {
    $creatorA = User::factory()->create(['store_id' => $this->storeA->id]);
    $creatorB = User::factory()->create(['store_id' => $this->storeB->id]);

    // Transaction for store A
    Transaction::create([
        'store_id' => $this->storeA->id,
        'customer_id' => $this->customer->id,
        'user_id' => $creatorA->id,
        'number' => 'TRX-A-001',
        'transaction_date' => now(),
        'type' => 'retail',
        'status' => 'completed',
        'payment_status' => 'paid',
        'subtotal' => 100000,
        'grand_total' => 100000,
        'paid_amount' => 100000,
        'change_amount' => 0,
        'total_cost' => 60000,
        'total_profit' => 40000,
    ]);

    // Transaction for store B
    Transaction::create([
        'store_id' => $this->storeB->id,
        'customer_id' => $this->customer->id,
        'user_id' => $creatorB->id,
        'number' => 'TRX-B-001',
        'transaction_date' => now(),
        'type' => 'retail',
        'status' => 'completed',
        'payment_status' => 'paid',
        'subtotal' => 200000,
        'grand_total' => 200000,
        'paid_amount' => 200000,
        'change_amount' => 0,
        'total_cost' => 120000,
        'total_profit' => 80000,
    ]);

    $kasirA = User::factory()->create(['store_id' => $this->storeA->id]);
    $kasirA->assignRole('kasir');

    // Attempt to access without store_id or with store B's id
    $responseKasir = $this->actingAs($kasirA)->get('/transactions?store_id='.$this->storeB->id);
    $responseKasir->assertOk();
    $responseKasir->assertInertia(fn ($page) => $page
        ->component('transactions/index')
        ->where('canFilterStore', false)
        ->where('filters.store_id', $this->storeA->id)
        ->has('options.stores', 1)
        ->where('options.stores.0.value', $this->storeA->id)
        ->has('transactions.data', 1)
        ->where('transactions.data.0.number', 'TRX-A-001')
        ->where('summary.total_count', 1)
    );
});

test('non owner user can only view their assigned store on services', function () {
    // Service order for Store A
    ServiceOrder::create([
        'number' => 'SO-A-001',
        'store_id' => $this->storeA->id,
        'customer_name' => 'Budi A',
        'customer_phone' => '0811111111',
        'plate_number' => 'B 1111 AA',
        'vehicle_brand' => 'Honda',
        'vehicle_model' => 'Beat',
        'status' => 'checkin',
        'checkin_at' => now(),
        'general_complaint' => 'Servis Ringan A',
        'estimated_total' => 50000,
    ]);

    // Service order for Store B
    ServiceOrder::create([
        'number' => 'SO-B-001',
        'store_id' => $this->storeB->id,
        'customer_name' => 'Budi B',
        'customer_phone' => '0822222222',
        'plate_number' => 'B 2222 BB',
        'vehicle_brand' => 'Yamaha',
        'vehicle_model' => 'NMAX',
        'status' => 'checkin',
        'checkin_at' => now(),
        'general_complaint' => 'Servis Ringan B',
        'estimated_total' => 100000,
    ]);

    $mekanikA = User::factory()->create(['store_id' => $this->storeA->id]);
    $mekanikA->assignRole('mekanik');

    // Attempt to access without store_id or with store B's id
    $responseMekanik = $this->actingAs($mekanikA)->get('/services?store_id='.$this->storeB->id);
    $responseMekanik->assertOk();
    $responseMekanik->assertInertia(fn ($page) => $page
        ->component('services/index')
        ->where('canFilterStore', false)
        ->where('filters.store_id', $this->storeA->id)
        ->has('options.stores', 1)
        ->where('options.stores.0.value', $this->storeA->id)
        ->has('serviceOrders.data', 1)
        ->where('serviceOrders.data.0.number', 'SO-A-001')
        ->where('summary.total_count', 1)
    );
});
