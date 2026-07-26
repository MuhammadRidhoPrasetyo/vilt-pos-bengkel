<?php

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('attributes belong to specific product directly', function () {
    $user = User::factory()->create();
    $category = ProductCategory::create(['name' => 'Oli & Pelumas', 'pricing_mode' => 'fixed']);

    $product = Product::create([
        'product_category_id' => $category->id,
        'name' => 'Oli Yamalube Matic',
        'item_type' => 'part',
        'has_variants' => true,
    ]);

    $attrVolume = $product->attributes()->create(['name' => 'Volume']);
    $attrVolume->options()->create(['value' => '0.8L']);

    $product->refresh();

    expect($product->attributes)->toHaveCount(1)
        ->and($product->attributes->first()->name)->toBe('Volume')
        ->and($product->attributes->first()->options->first()->value)->toBe('0.8L');
});
