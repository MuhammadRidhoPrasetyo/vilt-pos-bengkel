<?php

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('product and variant support receipt_name with fallback hierarchy', function () {
    $category = ProductCategory::create(['name' => 'Oli', 'pricing_mode' => 'fixed']);

    // 1. Fallback to product name + name_suffix when receipt_name is null
    $product1 = Product::create([
        'product_category_id' => $category->id,
        'name' => 'Oli Mesin Yamalube Matic 4T 20W-40 0.8L Bottle',
        'item_type' => 'part',
        'has_variants' => true,
    ]);

    $variant1 = ProductVariant::create([
        'product_id' => $product1->id,
        'sku' => 'YAMA-08L',
        'name_suffix' => '0.8L',
        'default_purchase_price' => 35000,
        'default_selling_price' => 45000,
        'is_active' => true,
    ]);

    expect($product1->display_receipt_name)->toBe('Oli Mesin Yamalube Matic 4T 20W-40 0.8L Bottle')
        ->and($variant1->load('product')->display_receipt_name)->toBe('Oli Mesin Yamalube Matic 4T 20W-40 0.8L Bottle 0.8L');

    // 2. Fallback to product receipt_name + name_suffix when variant receipt_name is null
    $product1->update(['receipt_name' => 'Oli Yamalube']);

    expect($product1->fresh()->display_receipt_name)->toBe('Oli Yamalube')
        ->and($variant1->fresh(['product'])->display_receipt_name)->toBe('Oli Yamalube 0.8L');

    // 3. Specific variant receipt_name overrides product receipt_name
    $variant1->update(['receipt_name' => 'Oli Yamalube Struk Custom 0.8L']);

    expect($variant1->fresh(['product'])->display_receipt_name)->toBe('Oli Yamalube Struk Custom 0.8L');
});

test('HTTP controller endpoints store and update receipt_name', function () {
    $user = User::factory()->create();
    $category = ProductCategory::create(['name' => 'General', 'pricing_mode' => 'fixed']);

    // Store Product with receipt_name
    $this->actingAs($user)
        ->post(route('products.store'), [
            'product_category_id' => $category->id,
            'name' => 'Ban Motor Tubeless Maxxis 90/90-14',
            'receipt_name' => 'Ban Maxxis 90/90-14',
            'item_type' => 'part',
            'has_variants' => false,
        ])
        ->assertRedirect(route('products.index'));

    $product = Product::where('name', 'Ban Motor Tubeless Maxxis 90/90-14')->firstOrFail();
    expect($product->receipt_name)->toBe('Ban Maxxis 90/90-14')
        ->and($product->display_receipt_name)->toBe('Ban Maxxis 90/90-14');

    // Store Variant with receipt_name
    $this->actingAs($user)
        ->post(route('product-variants.store'), [
            'product_id' => $product->id,
            'sku' => 'MAX-9090',
            'receipt_name' => 'Maxxis 90/90-14',
            'default_purchase_price' => 150000,
            'default_selling_price' => 200000,
            'is_active' => true,
        ])
        ->assertRedirect(route('product-variants.index'));

    $variant = ProductVariant::where('sku', 'MAX-9090')->firstOrFail();
    expect($variant->receipt_name)->toBe('Maxxis 90/90-14')
        ->and($variant->display_receipt_name)->toBe('Maxxis 90/90-14');
});
