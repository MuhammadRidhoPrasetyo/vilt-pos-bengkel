<?php

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('product variant automatically generates SKU when sku is not provided', function () {
    $category = ProductCategory::create(['name' => 'Oli & Pelumas', 'pricing_mode' => 'fixed']);

    $product = Product::create([
        'product_category_id' => $category->id,
        'name' => 'Federal Matic 10W-30',
        'item_type' => 'part',
        'has_variants' => false,
    ]);

    $variant1 = ProductVariant::create([
        'product_id' => $product->id,
        'sku' => null,
        'default_purchase_price' => 35000,
        'default_selling_price' => 45000,
        'is_active' => true,
    ]);

    expect($variant1->sku)->toBe('OLI-FM1-001');

    $variant2 = ProductVariant::create([
        'product_id' => $product->id,
        'sku' => null,
        'default_purchase_price' => 35000,
        'default_selling_price' => 45000,
        'is_active' => true,
    ]);

    expect($variant2->sku)->toBe('OLI-FM1-002');
});

test('product variant preserves manually provided SKU', function () {
    $category = ProductCategory::create(['name' => 'Oli & Pelumas', 'pricing_mode' => 'fixed']);

    $product = Product::create([
        'product_category_id' => $category->id,
        'name' => 'Federal Matic 10W-30',
        'item_type' => 'part',
        'has_variants' => false,
    ]);

    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'sku' => 'CUSTOM-SKU-123',
        'default_purchase_price' => 35000,
        'default_selling_price' => 45000,
        'is_active' => true,
    ]);

    expect($variant->sku)->toBe('CUSTOM-SKU-123');
});
