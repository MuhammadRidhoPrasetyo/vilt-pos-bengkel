<?php

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseLocation;
use Database\Seeders\UserRolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(UserRolePermissionSeeder::class);

    $this->user = User::factory()->create();
    $this->user->assignRole('owner');

    $this->store = Store::create([
        'code' => 'STR-WH',
        'name' => 'Store Utama',
        'address' => 'Jl. Bengkel No. 1',
    ]);

    $this->warehouse = Warehouse::create([
        'store_id' => $this->store->id,
        'code' => 'WH-01',
        'name' => 'Gudang Pusat',
    ]);
});

test('warehouse location generates multi level hierarchical full path', function () {
    $rak1 = WarehouseLocation::create([
        'warehouse_id' => $this->warehouse->id,
        'name' => 'Rak 1',
        'code' => 'R1',
        'type' => 'rack',
        'is_active' => true,
    ]);

    expect($rak1->full_path)->toBe('Rak 1');

    $rak2 = WarehouseLocation::create([
        'warehouse_id' => $this->warehouse->id,
        'parent_id' => $rak1->id,
        'name' => 'Rak 2',
        'code' => 'R2',
        'type' => 'shelf',
        'is_active' => true,
    ]);

    expect($rak2->full_path)->toBe('Rak 1 / Rak 2');

    $rak3 = WarehouseLocation::create([
        'warehouse_id' => $this->warehouse->id,
        'parent_id' => $rak2->id,
        'name' => 'Rak 3',
        'code' => 'R3',
        'type' => 'bin',
        'is_active' => true,
    ]);

    expect($rak3->full_path)->toBe('Rak 1 / Rak 2 / Rak 3');
});

test('products table includes warehouse locations with full hierarchical chain', function () {
    $cat = ProductCategory::create([
        'name' => 'Sparepart Mesin',
    ]);

    $rak1 = WarehouseLocation::create([
        'warehouse_id' => $this->warehouse->id,
        'name' => 'Rak 1',
        'code' => 'R1',
        'type' => 'rack',
        'is_active' => true,
    ]);

    $rak2 = WarehouseLocation::create([
        'warehouse_id' => $this->warehouse->id,
        'parent_id' => $rak1->id,
        'name' => 'Rak 2',
        'code' => 'R2',
        'type' => 'shelf',
        'is_active' => true,
    ]);

    $rak3 = WarehouseLocation::create([
        'warehouse_id' => $this->warehouse->id,
        'parent_id' => $rak2->id,
        'name' => 'Rak 3',
        'code' => 'R3',
        'type' => 'bin',
        'is_active' => true,
    ]);

    $product = Product::create([
        'name' => 'Piston Kit Honda',
        'product_category_id' => $cat->id,
        'item_type' => 'part',
        'is_active' => true,
    ]);

    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'sku' => 'PST-001',
        'default_purchase_price' => 100000,
        'default_selling_price' => 150000,
    ]);

    ProductStock::create([
        'product_variant_id' => $variant->id,
        'warehouse_id' => $this->warehouse->id,
        'warehouse_location_id' => $rak3->id,
        'quantity' => 15,
        'minimum_stock' => 5,
    ]);

    $response = $this->actingAs($this->user)->get('/products');
    $response->assertOk();

    $response->assertInertia(fn ($page) => $page
        ->component('products/index')
        ->has('records.data', 1)
        ->where('records.data.0.name', 'Piston Kit Honda')
        ->where('records.data.0.warehouse_locations', ['Rak 1 / Rak 2 / Rak 3'])
        ->where('config.fields', fn ($fields) => collect($fields)->contains(fn ($field) => $field['name'] === 'warehouse_locations'
            && $field['table'] === true
        ))
    );
});
