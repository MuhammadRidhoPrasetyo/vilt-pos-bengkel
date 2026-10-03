<?php

use App\Models\Partner;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->store = Store::create([
        'name' => 'Store Active Test',
        'code' => 'SAT',
    ]);

    Permission::firstOrCreate(['name' => 'products.delete', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'product-variants.delete', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'transactions.create', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'services.create', 'guard_name' => 'web']);

    $this->adminUser = User::factory()->create([
        'store_id' => $this->store->id,
    ]);
    $this->adminUser->givePermissionTo(['products.delete', 'product-variants.delete', 'transactions.create', 'services.create']);

    $this->regularUser = User::factory()->create([
        'store_id' => $this->store->id,
    ]);

    $this->category = ProductCategory::create(['name' => 'Oli Mesin']);
    $this->warehouse = Warehouse::create([
        'store_id' => $this->store->id,
        'code' => 'GDG-01',
        'name' => 'Gudang Utama',
    ]);

    $this->payment = Payment::create(['name' => 'Cash', 'type' => 'cash']);
});

test('cashier pos only loads active variants and products', function () {
    // 1. Active Product and Active Variant
    $prodActive = Product::create([
        'product_category_id' => $this->category->id,
        'name' => 'Oli Castrol Active',
        'item_type' => 'part',
        'is_active' => true,
    ]);
    $varActive = ProductVariant::create([
        'product_id' => $prodActive->id,
        'sku' => 'VAR-ACT-001',
        'default_selling_price' => 50000,
        'is_active' => true,
    ]);

    // 2. Active Product but Inactive Variant
    $varInactive = ProductVariant::create([
        'product_id' => $prodActive->id,
        'sku' => 'VAR-INACT-001',
        'default_selling_price' => 55000,
        'is_active' => false,
    ]);

    // 3. Inactive Product with Active Variant
    $prodInactive = Product::create([
        'product_category_id' => $this->category->id,
        'name' => 'Oli Shell Nonaktif',
        'item_type' => 'part',
        'is_active' => false,
    ]);
    $varProdInactive = ProductVariant::create([
        'product_id' => $prodInactive->id,
        'sku' => 'VAR-PROD-INACT-001',
        'default_selling_price' => 60000,
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->adminUser)->get(route('transactions.create'));
    $response->assertOk();

    $variants = collect($response->viewData('page')['props']['variants']['data']);
    $variantIds = $variants->pluck('id')->all();

    expect($variantIds)->toContain($varActive->id);
    expect($variantIds)->not->toContain($varInactive->id);
    expect($variantIds)->not->toContain($varProdInactive->id);
});

test('service order create only loads active variants and products', function () {
    // Active Labor Product
    $prodLabor = Product::create([
        'product_category_id' => $this->category->id,
        'name' => 'Jasa Tune Up',
        'item_type' => 'labor',
        'is_active' => true,
    ]);
    $varLabor = ProductVariant::create([
        'product_id' => $prodLabor->id,
        'sku' => 'JSA-TUNE-001',
        'default_selling_price' => 75000,
        'is_active' => true,
    ]);

    // Inactive Labor Product
    $prodLaborInactive = Product::create([
        'product_category_id' => $this->category->id,
        'name' => 'Jasa Overhaul Mati',
        'item_type' => 'labor',
        'is_active' => false,
    ]);
    $varLaborInactive = ProductVariant::create([
        'product_id' => $prodLaborInactive->id,
        'sku' => 'JSA-OVH-001',
        'default_selling_price' => 500000,
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->adminUser)->get(route('services.create'));
    $response->assertOk();

    $variants = collect($response->viewData('page')['props']['variants']['data']);
    $variantIds = $variants->pluck('id')->all();

    expect($variantIds)->toContain($varLabor->id);
    expect($variantIds)->not->toContain($varLaborInactive->id);
});

test('user without delete permission cannot delete product or variant', function () {
    $product = Product::create([
        'product_category_id' => $this->category->id,
        'name' => 'Produk Tes Hak Akses',
        'item_type' => 'part',
    ]);
    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'sku' => 'VAR-HAK-001',
        'default_selling_price' => 20000,
    ]);

    // Regular user without delete permission
    $this->actingAs($this->regularUser)
        ->delete(route('products.destroy', $product->id))
        ->assertForbidden();

    $this->actingAs($this->regularUser)
        ->delete(route('product-variants.destroy', $variant->id))
        ->assertForbidden();
});

test('unused product and variant can be deleted', function () {
    $product = Product::create([
        'product_category_id' => $this->category->id,
        'name' => 'Produk Belum Dipakai',
        'item_type' => 'part',
    ]);
    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'sku' => 'VAR-UNUSED-001',
        'default_selling_price' => 25000,
    ]);

    // Delete variant first
    $this->actingAs($this->adminUser)
        ->delete(route('product-variants.destroy', $variant->id))
        ->assertSessionHas('success');

    expect(ProductVariant::find($variant->id))->toBeNull();
    expect(ProductVariant::withTrashed()->find($variant->id))->not->toBeNull();

    // Delete product
    $this->actingAs($this->adminUser)
        ->delete(route('products.destroy', $product->id))
        ->assertSessionHas('success');

    expect(Product::find($product->id))->toBeNull();
    expect(Product::withTrashed()->find($product->id))->not->toBeNull();
});

test('product and variant that have transactions cannot be deleted', function () {
    $product = Product::create([
        'product_category_id' => $this->category->id,
        'name' => 'Produk Sudah Dijual',
        'item_type' => 'part',
    ]);
    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'sku' => 'VAR-USED-001',
        'default_selling_price' => 30000,
    ]);
    ProductStock::create([
        'product_variant_id' => $variant->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 10,
    ]);

    $customer = Partner::create([
        'store_id' => $this->store->id,
        'code' => 'CUST-USED-01',
        'kind' => 'person',
        'name' => 'Pelanggan Used',
    ]);

    // Record a sale transaction
    $txService = app(TransactionService::class);
    $txService->create([
        'store_id' => $this->store->id,
        'customer_id' => $customer->id,
        'payment_id' => $this->payment->id,
        'type' => 'retail',
        'items' => [
            [
                'product_variant_id' => $variant->id,
                'quantity' => 1,
                'unit_price' => 30000,
            ],
        ],
    ], $this->adminUser->id);

    // Attempt to delete variant
    $this->actingAs($this->adminUser)
        ->delete(route('product-variants.destroy', $variant->id))
        ->assertSessionHas('error');

    // Attempt to delete product
    $this->actingAs($this->adminUser)
        ->delete(route('products.destroy', $product->id))
        ->assertSessionHas('error');

    // Verify both still exist
    expect(ProductVariant::find($variant->id))->not->toBeNull();
    expect(Product::find($product->id))->not->toBeNull();
});
