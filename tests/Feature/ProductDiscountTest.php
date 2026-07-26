<?php

use App\Models\DiscountType;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductDiscount;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('can create global and store specific product discounts', function () {
    $user = User::factory()->create();
    $category = ProductCategory::create(['name' => 'Oli & Pelumas', 'pricing_mode' => 'fixed']);
    $store = Store::create(['name' => 'Bengkel Pusat', 'code' => 'PUSAT']);
    $discountType = DiscountType::create(['name' => 'Promo Member 10%']);

    $product = Product::create([
        'product_category_id' => $category->id,
        'name' => 'Oli Yamalube Matic',
        'item_type' => 'part',
        'has_variants' => false,
    ]);

    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'is_active' => true,
    ]);

    // 1. Create global discount (store_id = null)
    $this->actingAs($user)
        ->post(route('product-discounts.store'), [
            'product_variant_id' => $variant->id,
            'store_id' => null,
            'discount_type_id' => $discountType->id,
            'type' => 'percent',
            'value' => 10,
        ])
        ->assertRedirect();

    expect(ProductDiscount::count())->toBe(1);
    $globalDiscount = ProductDiscount::first();
    expect($globalDiscount->store_id)->toBeNull()
        ->and($globalDiscount->type)->toBe('percent')
        ->and((float) $globalDiscount->value)->toBe(10.0);

    // 2. Create store specific discount
    $this->actingAs($user)
        ->post(route('product-discounts.store'), [
            'product_variant_id' => $variant->id,
            'store_id' => $store->id,
            'discount_type_id' => $discountType->id,
            'type' => 'amount',
            'value' => 15000,
        ])
        ->assertRedirect();

    expect(ProductDiscount::count())->toBe(2);
    $storeDiscount = ProductDiscount::where('store_id', $store->id)->first();
    expect($storeDiscount->store_id)->toBe($store->id)
        ->and($storeDiscount->type)->toBe('amount')
        ->and((float) $storeDiscount->value)->toBe(15000.0);

    // 3. Delete discount
    $this->actingAs($user)
        ->delete(route('product-discounts.destroy', $storeDiscount))
        ->assertRedirect();

    expect(ProductDiscount::count())->toBe(1);
});
