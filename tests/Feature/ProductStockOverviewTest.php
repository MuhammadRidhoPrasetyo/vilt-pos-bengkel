<?php

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can view product stocks index page with summary metrics', function () {
    $user = User::factory()->create();
    $store = Store::create(['name' => 'Bengkel Pusat', 'code' => 'PUSAT']);
    $category = ProductCategory::create(['name' => 'Sparepart', 'pricing_mode' => 'fixed']);
    $warehouse = Warehouse::create(['name' => 'Gudang Utama', 'code' => 'GDG-UTAMA', 'store_id' => $store->id]);

    $product = Product::create([
        'product_category_id' => $category->id,
        'name' => 'Oli Mesin',
        'item_type' => 'part',
        'has_variants' => false,
    ]);

    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'is_active' => true,
    ]);

    ProductStock::create([
        'product_variant_id' => $variant->id,
        'warehouse_id' => $warehouse->id,
        'quantity' => 20,
        'minimum_stock' => 5,
        'is_hidden' => false,
    ]);

    $this->actingAs($user)
        ->get(route('product-stocks.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('product-stocks/index')
            ->has('records.data', 1)
            ->where('summary.total_quantity', 20)
            ->where('summary.total_items', 1)
            ->where('summary.low_stock_count', 0)
        );
});
