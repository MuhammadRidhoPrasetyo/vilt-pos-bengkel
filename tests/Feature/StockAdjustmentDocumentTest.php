<?php

use App\Models\InventoryBatch;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Models\StockAdjustment;
use App\Models\Store;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function stockAdjustmentSetup(): array
{
    $user = User::factory()->create();
    $store = Store::create(['name' => 'Bengkel Utama', 'code' => 'BKL-01']);
    $warehouse = Warehouse::create(['store_id' => $store->id, 'code' => 'GDG-01', 'name' => 'Gudang Utama']);
    $category = ProductCategory::create(['name' => 'Sparepart', 'pricing_mode' => 'fixed']);
    $product = Product::create(['product_category_id' => $category->id, 'name' => 'Busi', 'item_type' => 'part', 'has_variants' => false]);
    $variant = ProductVariant::create(['product_id' => $product->id, 'default_purchase_price' => 10000, 'is_active' => true]);

    return compact('user', 'store', 'warehouse', 'variant');
}

test('stock adjustment draft can be created and posted as stock increase', function () {
    ['user' => $user, 'store' => $store, 'warehouse' => $warehouse, 'variant' => $variant] = stockAdjustmentSetup();

    $this->actingAs($user)
        ->post(route('stock-adjustments.store'), [
            'store_id' => $store->id,
            'items' => [[
                'product_variant_id' => $variant->id,
                'warehouse_id' => $warehouse->id,
                'adjustment_type' => 'increase',
                'quantity' => 5,
                'unit_cost' => 12000,
            ]],
        ])
        ->assertRedirect();

    $adjustment = StockAdjustment::with('items')->first();
    expect($adjustment->status)->toBe('draft')
        ->and(ProductStock::count())->toBe(0);

    $this->actingAs($user)
        ->post(route('stock-adjustments.post', $adjustment))
        ->assertRedirect(route('stock-adjustments.show', $adjustment));

    expect($adjustment->fresh()->status)->toBe('posted')
        ->and(ProductStock::first()->quantity)->toBe(5)
        ->and((float) InventoryBatch::first()->unit_cost)->toBe(12000.0)
        ->and(InventoryMovement::first()->type)->toBe('in')
        ->and(InventoryMovement::first()->reference_type)->toBe(StockAdjustment::class);
});

test('stock adjustment decrease consumes fifo batches and rejects insufficient stock', function () {
    ['user' => $user, 'store' => $store, 'warehouse' => $warehouse, 'variant' => $variant] = stockAdjustmentSetup();

    ProductStock::create(['product_variant_id' => $variant->id, 'warehouse_id' => $warehouse->id, 'quantity' => 5]);
    $firstBatch = InventoryBatch::create(['product_variant_id' => $variant->id, 'warehouse_id' => $warehouse->id, 'initial_quantity' => 2, 'current_quantity' => 2, 'unit_cost' => 1000, 'received_at' => now()->subDays(2)]);
    $secondBatch = InventoryBatch::create(['product_variant_id' => $variant->id, 'warehouse_id' => $warehouse->id, 'initial_quantity' => 3, 'current_quantity' => 3, 'unit_cost' => 2000, 'received_at' => now()->subDay()]);

    $adjustment = StockAdjustment::create(['store_id' => $store->id, 'status' => 'draft', 'occurred_at' => now()]);
    $adjustment->items()->create(['product_id' => $variant->product_id, 'product_variant_id' => $variant->id, 'warehouse_id' => $warehouse->id, 'adjustment_type' => 'decrease', 'quantity' => 4, 'unit_cost' => 0]);

    $this->actingAs($user)->post(route('stock-adjustments.post', $adjustment))->assertRedirect();

    expect(ProductStock::first()->quantity)->toBe(1)
        ->and($firstBatch->fresh()->current_quantity)->toBe(0)
        ->and($secondBatch->fresh()->current_quantity)->toBe(1)
        ->and(InventoryMovement::where('type', 'out')->count())->toBe(2);

    $over = StockAdjustment::create(['store_id' => $store->id, 'status' => 'draft', 'occurred_at' => now()]);
    $over->items()->create(['product_id' => $variant->product_id, 'product_variant_id' => $variant->id, 'warehouse_id' => $warehouse->id, 'adjustment_type' => 'decrease', 'quantity' => 9, 'unit_cost' => 0]);

    $this->actingAs($user)->post(route('stock-adjustments.post', $over))->assertSessionHasErrors('items');
    expect(ProductStock::first()->quantity)->toBe(1);
});

test('posted stock adjustment is immutable while draft can be cancelled and deleted', function () {
    ['user' => $user, 'store' => $store, 'warehouse' => $warehouse, 'variant' => $variant] = stockAdjustmentSetup();

    $draft = StockAdjustment::create(['store_id' => $store->id, 'status' => 'draft', 'occurred_at' => now()]);
    $draft->items()->create(['product_id' => $variant->product_id, 'product_variant_id' => $variant->id, 'warehouse_id' => $warehouse->id, 'adjustment_type' => 'increase', 'quantity' => 1, 'unit_cost' => 1000]);

    $this->actingAs($user)->post(route('stock-adjustments.cancel', $draft))->assertRedirect();
    expect($draft->fresh()->status)->toBe('cancelled');

    $posted = StockAdjustment::create(['store_id' => $store->id, 'status' => 'posted', 'occurred_at' => now()]);
    $this->actingAs($user)->delete(route('stock-adjustments.destroy', $posted))->assertSessionHasErrors('status');
    expect($posted->fresh())->not->toBeNull();
});
