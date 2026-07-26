<?php

use App\Models\Attribute;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('can create, update, and delete product-owned attributes and options', function () {
    $user = User::factory()->create();
    $category = ProductCategory::create(['name' => 'Oli & Pelumas', 'pricing_mode' => 'fixed']);

    $product = Product::create([
        'product_category_id' => $category->id,
        'name' => 'Oli Yamalube Matic',
        'item_type' => 'part',
        'has_variants' => true,
    ]);

    // 1. Create product attribute
    $this->actingAs($user)
        ->post(route('products.attributes.store', $product), [
            'name' => 'Viskositas',
            'options' => ['10W-40', '20W-50'],
        ])
        ->assertRedirect();

    $product->refresh();
    expect($product->attributes)->toHaveCount(1);

    $attribute = $product->attributes->first();
    expect($attribute->name)->toBe('Viskositas')
        ->and($attribute->options)->toHaveCount(2)
        ->and($attribute->options->pluck('value')->all())->toBe(['10W-40', '20W-50']);

    // 2. Update product attribute
    $this->actingAs($user)
        ->put(route('product-attributes.update', $attribute), [
            'name' => 'Viskositas Oli',
            'options' => ['10W-40', '15W-40', '20W-50'],
        ])
        ->assertRedirect();

    $attribute->refresh();
    expect($attribute->name)->toBe('Viskositas Oli')
        ->and($attribute->options)->toHaveCount(3);

    // 3. Delete product attribute
    $this->actingAs($user)
        ->delete(route('product-attributes.destroy', $attribute))
        ->assertRedirect();

    expect(Attribute::find($attribute->id))->toBeNull();
});
