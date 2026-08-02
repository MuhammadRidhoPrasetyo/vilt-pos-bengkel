<?php

use App\Models\CashFlow;
use App\Models\CashFlowCategory;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('authenticated user can view dashboard page with cash flows and stock alert data', function () {
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
        ->has('stockAlerts')
        ->has('stockSummary')
        ->has('canFilterStore')
        ->has('options')
    );
});

test('owner user can view all stock alerts and has store filtering enabled', function () {
    $ownerRole = Role::findOrCreate('owner', 'web');
    $owner = User::factory()->create();
    $owner->assignRole($ownerRole);

    $storeA = Store::create(['code' => 'STR-A', 'name' => 'Store A']);
    $storeB = Store::create(['code' => 'STR-B', 'name' => 'Store B']);

    $whA = Warehouse::create(['store_id' => $storeA->id, 'code' => 'WHA', 'name' => 'Gudang A']);
    $whB = Warehouse::create(['store_id' => $storeB->id, 'code' => 'WHB', 'name' => 'Gudang B']);

    $prodCat = ProductCategory::create(['name' => 'Sparepart General']);
    $product = Product::create(['name' => 'Oli Mesin', 'product_category_id' => $prodCat->id, 'item_type' => 'part']);
    $variantA = ProductVariant::create(['product_id' => $product->id, 'sku' => 'OLI-A', 'default_purchase_price' => 50000, 'default_selling_price' => 70000]);
    $variantB = ProductVariant::create(['product_id' => $product->id, 'sku' => 'OLI-B', 'default_purchase_price' => 50000, 'default_selling_price' => 70000]);

    ProductStock::create(['product_variant_id' => $variantA->id, 'warehouse_id' => $whA->id, 'quantity' => 0, 'minimum_stock' => 5]);
    ProductStock::create(['product_variant_id' => $variantB->id, 'warehouse_id' => $whB->id, 'quantity' => 2, 'minimum_stock' => 5]);

    $response = $this->actingAs($owner)->get('/dashboard');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Dashboard')
        ->where('canFilterStore', true)
        ->where('stockSummary.out_of_stock_count', 1)
        ->where('stockSummary.below_min_count', 1)
    );

    // Test owner filtering specific store
    $responseFiltered = $this->actingAs($owner)->get('/dashboard?stock_store_id='.$storeA->id);
    $responseFiltered->assertStatus(200);
    $responseFiltered->assertInertia(fn ($page) => $page
        ->where('stockStoreId', $storeA->id)
        ->where('stockSummary.total_alert_count', 1)
    );
});

test('cashier user is strictly restricted to assigned store stocks', function () {
    $storeA = Store::create(['code' => 'STR-A', 'name' => 'Store A']);
    $storeB = Store::create(['code' => 'STR-B', 'name' => 'Store B']);

    $kasirRole = Role::findOrCreate('kasir', 'web');
    $kasir = User::factory()->create(['store_id' => $storeA->id]);
    $kasir->assignRole($kasirRole);

    $whA = Warehouse::create(['store_id' => $storeA->id, 'code' => 'WHA', 'name' => 'Gudang A']);
    $whB = Warehouse::create(['store_id' => $storeB->id, 'code' => 'WHB', 'name' => 'Gudang B']);

    $prodCat = ProductCategory::create(['name' => 'Ban & Velg']);
    $product = Product::create(['name' => 'Ban Motor', 'product_category_id' => $prodCat->id, 'item_type' => 'part']);
    $variantA = ProductVariant::create(['product_id' => $product->id, 'sku' => 'BAN-A', 'default_purchase_price' => 150000, 'default_selling_price' => 200000]);
    $variantB = ProductVariant::create(['product_id' => $product->id, 'sku' => 'BAN-B', 'default_purchase_price' => 150000, 'default_selling_price' => 200000]);

    ProductStock::create(['product_variant_id' => $variantA->id, 'warehouse_id' => $whA->id, 'quantity' => 1, 'minimum_stock' => 10]);
    ProductStock::create(['product_variant_id' => $variantB->id, 'warehouse_id' => $whB->id, 'quantity' => 0, 'minimum_stock' => 10]);

    $response = $this->actingAs($kasir)->get('/dashboard');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Dashboard')
        ->where('canFilterStore', false)
        ->where('stockStoreId', $storeA->id)
        ->where('stockSummary.total_alert_count', 1)
    );
});
