<?php

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
});

test('can upload multiple images to a product and delete specific media', function () {
    $user = User::factory()->create();
    $category = ProductCategory::create(['name' => 'Oli', 'pricing_mode' => 'fixed']);

    $image1 = UploadedFile::fake()->image('front.jpg', 400, 400);
    $image2 = UploadedFile::fake()->image('back.png', 400, 400);

    // 1. Create product with multiple images
    $this->actingAs($user)
        ->post(route('products.store'), [
            'product_category_id' => $category->id,
            'name' => 'Oli Yamalube Matic',
            'item_type' => 'part',
            'has_variants' => false,
            'images' => [$image1, $image2],
        ])
        ->assertRedirect(route('products.index'));

    $product = Product::where('name', 'Oli Yamalube Matic')->firstOrFail();

    expect($product->getMedia('images'))->toHaveCount(2);

    $firstMedia = $product->getFirstMedia('images');

    // 2. Update product & delete the first media item
    $image3 = UploadedFile::fake()->image('detail.webp', 400, 400);

    $this->actingAs($user)
        ->post(route('products.update', $product), [
            '_method' => 'put',
            'product_category_id' => $category->id,
            'name' => 'Oli Yamalube Matic Updated',
            'item_type' => 'part',
            'has_variants' => false,
            'delete_media_ids' => [$firstMedia->id],
            'images' => [$image3],
        ])
        ->assertRedirect(route('products.index'));

    $product->refresh();

    expect($product->getMedia('images'))->toHaveCount(2)
        ->and($product->getMedia('images')->pluck('id'))->not->toContain($firstMedia->id);
});

test('can upload multiple images to a product variant', function () {
    $user = User::factory()->create();
    $category = ProductCategory::create(['name' => 'Sparepart', 'pricing_mode' => 'fixed']);
    $product = Product::create([
        'product_category_id' => $category->id,
        'name' => 'Busi Motor',
        'item_type' => 'part',
        'has_variants' => true,
    ]);

    $varImage1 = UploadedFile::fake()->image('busi_01.jpg', 500, 500);

    $this->actingAs($user)
        ->post(route('product-variants.store'), [
            'product_id' => $product->id,
            'sku' => 'BUSI-CPR8',
            'default_purchase_price' => 15000,
            'default_selling_price' => 25000,
            'is_active' => true,
            'images' => [$varImage1],
        ])
        ->assertRedirect(route('product-variants.index'));

    $variant = ProductVariant::where('sku', 'BUSI-CPR8')->firstOrFail();

    expect($variant->getMedia('images'))->toHaveCount(1)
        ->and($variant->getFirstMedia('images')->file_name)->toBe('busi_01.jpg');
});
