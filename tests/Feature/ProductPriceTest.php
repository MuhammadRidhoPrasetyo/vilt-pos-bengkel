<?php

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductPrice;
use App\Models\ProductPriceHistory;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('setting store price creates product price and price history snapshot for global and store specific prices', function () {
    $user = User::factory()->create();
    $store = Store::create(['name' => 'Bengkel Pusat', 'code' => 'PUSAT']);
    $category = ProductCategory::create(['name' => 'Sparepart', 'pricing_mode' => 'fixed']);

    $product = Product::create([
        'product_category_id' => $category->id,
        'name' => 'Kampas Rem',
        'item_type' => 'part',
        'has_variants' => false,
    ]);

    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'is_active' => true,
    ]);

    // 1. Create global product price (store_id = null)
    $this->actingAs($user)
        ->post(route('product-prices.store'), [
            'product_variant_id' => $variant->id,
            'store_id' => null,
            'purchase_price' => 30000,
            'selling_price' => 40000,
            'is_active' => true,
        ])
        ->assertRedirect();

    expect(ProductPrice::count())->toBe(1);
    $globalPrice = ProductPrice::first();
    expect($globalPrice->store_id)->toBeNull()
        ->and((float) $globalPrice->purchase_price)->toBe(30000.0)
        ->and((float) $globalPrice->selling_price)->toBe(40000.0);

    expect(ProductPriceHistory::count())->toBe(1);
    $globalHistory = ProductPriceHistory::first();
    expect($globalHistory->store_id)->toBeNull()
        ->and((float) $globalHistory->selling_price)->toBe(40000.0);

    // 2. Create store specific price (store_id = $store->id)
    $this->actingAs($user)
        ->post(route('product-prices.store'), [
            'product_variant_id' => $variant->id,
            'store_id' => $store->id,
            'purchase_price' => 35000,
            'selling_price' => 45000,
            'is_active' => true,
        ])
        ->assertRedirect();

    expect(ProductPrice::count())->toBe(2);
    $storePrice = ProductPrice::where('store_id', $store->id)->first();
    expect($storePrice->store_id)->toBe($store->id)
        ->and((float) $storePrice->selling_price)->toBe(45000.0);

    // 3. Update store price (Purchase: 35.000, Selling: 50.000)
    $this->actingAs($user)
        ->put(route('product-prices.update', $storePrice), [
            'product_variant_id' => $variant->id,
            'store_id' => $store->id,
            'purchase_price' => 35000,
            'selling_price' => 50000,
            'is_active' => true,
        ])
        ->assertRedirect();

    expect((float) ProductPrice::where('store_id', $store->id)->first()->selling_price)->toBe(50000.0);
    expect(ProductPriceHistory::count())->toBe(3);

    $latestHistory = ProductPriceHistory::orderBy('created_at', 'desc')->orderBy('id', 'desc')->first();
    expect((float) $latestHistory->selling_price)->toBe(50000.0);

    // 4. Delete store price
    $this->actingAs($user)
        ->delete(route('product-prices.destroy', $storePrice))
        ->assertRedirect();

    expect(ProductPrice::count())->toBe(1);
});
