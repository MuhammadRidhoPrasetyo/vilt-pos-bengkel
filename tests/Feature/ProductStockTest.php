<?php

use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('initializing stock creates product stock and inventory movement log', function () {
    $user = User::factory()->create();
    $store = Store::create(['name' => 'Pusat', 'code' => 'PUSAT']);
    $category = ProductCategory::create(['name' => 'Sparepart', 'pricing_mode' => 'fixed']);
    $warehouse = Warehouse::create(['store_id' => $store->id, 'name' => 'Gudang Utama', 'code' => 'WH-01']);
    $location = WarehouseLocation::create([
        'warehouse_id' => $warehouse->id,
        'code' => 'RAK-01',
        'name' => 'Rak Utama',
    ]);

    $product = Product::create([
        'product_category_id' => $category->id,
        'name' => 'Busi Racing NGK',
        'item_type' => 'part',
        'has_variants' => false,
    ]);

    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'is_active' => true,
    ]);

    // 1. Initialize stock (50 Pcs)
    $this->actingAs($user)
        ->post(route('product-stocks.store'), [
            'product_variant_id' => $variant->id,
            'warehouse_id' => $warehouse->id,
            'warehouse_location_id' => $location->id,
            'quantity' => 50,
            'minimum_stock' => 10,
            'is_hidden' => false,
        ])
        ->assertRedirect();

    expect(ProductStock::count())->toBe(1);
    $stock = ProductStock::first();
    expect($stock->quantity)->toBe(50)
        ->and($stock->warehouse_id)->toBe($warehouse->id)
        ->and($stock->warehouse_location_id)->toBe($location->id);

    // Verify InventoryMovement log automatically created
    expect(InventoryMovement::count())->toBe(1);
    $movement = InventoryMovement::first();
    expect($movement->type)->toBe('in')
        ->and($movement->reference_type)->toBe('initial_stock')
        ->and($movement->quantity)->toBe(50)
        ->and($movement->balance_after)->toBe(50);

    // 2. Adjust stock (increase to 70 Pcs)
    $this->actingAs($user)
        ->put(route('product-stocks.update', $stock), [
            'product_variant_id' => $variant->id,
            'warehouse_id' => $warehouse->id,
            'warehouse_location_id' => $location->id,
            'quantity' => 70,
            'minimum_stock' => 10,
            'is_hidden' => false,
        ])
        ->assertRedirect();

    expect(ProductStock::first()->quantity)->toBe(70);
    expect(InventoryMovement::count())->toBe(2);
    $latestMovement = InventoryMovement::orderBy('created_at', 'desc')->orderBy('id', 'desc')->first();
    expect($latestMovement->type)->toBe('in')
        ->and($latestMovement->reference_type)->toBe('stock_adjustment')
        ->and($latestMovement->quantity)->toBe(20)
        ->and($latestMovement->balance_after)->toBe(70);

    // 3. Delete stock
    $this->actingAs($user)
        ->delete(route('product-stocks.destroy', $stock))
        ->assertRedirect();

    expect(ProductStock::count())->toBe(0);
    expect(InventoryMovement::count())->toBe(3);
    $deleteMovement = InventoryMovement::orderBy('created_at', 'desc')->orderBy('id', 'desc')->first();
    expect($deleteMovement->type)->toBe('out')
        ->and($deleteMovement->reference_type)->toBe('stock_removal')
        ->and($deleteMovement->quantity)->toBe(70)
        ->and($deleteMovement->balance_after)->toBe(0);
});
