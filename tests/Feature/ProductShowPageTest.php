<?php

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('authenticated user can view product show page with tabs and variant data', function () {
    $user = User::factory()->create();
    $category = ProductCategory::create(['name' => 'Oli & Pelumas', 'pricing_mode' => 'fixed']);

    $product = Product::create([
        'product_category_id' => $category->id,
        'name' => 'Oli Yamalube Matic 4T',
        'receipt_name' => 'Oli Yamalube',
        'item_type' => 'part',
        'has_variants' => true,
    ]);

    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'sku' => 'YAMA-08L',
        'name_suffix' => '0.8L',
        'default_purchase_price' => 35000,
        'default_selling_price' => 45000,
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->get(route('products.show', $product))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('products/show')
            ->has('product')
            ->has('variants')
            ->where('product.data.id', $product->id)
            ->where('product.data.name', 'Oli Yamalube Matic 4T')
        );
});

test('authenticated user can view product variant show page', function () {
    $user = User::factory()->create();
    $category = ProductCategory::create(['name' => 'Oli & Pelumas', 'pricing_mode' => 'fixed']);

    $product = Product::create([
        'product_category_id' => $category->id,
        'name' => 'Oli Yamalube Matic 4T',
        'item_type' => 'part',
        'has_variants' => true,
    ]);

    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'sku' => 'YAMA-08L',
        'name_suffix' => '0.8L',
        'default_purchase_price' => 35000,
        'default_selling_price' => 45000,
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->get(route('product-variants.show', $variant))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('product-variants/show')
            ->has('productVariant')
            ->where('productVariant.data.id', $variant->id)
            ->where('productVariant.data.sku', 'YAMA-08L')
        );
});
