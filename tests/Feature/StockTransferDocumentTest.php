<?php

use App\Models\InventoryBatch;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Models\StockTransfer;
use App\Models\Store;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function stockTransferSetup(): array
{
    $user = User::factory()->create();
    $fromStore = Store::create(['name' => 'Toko Asal', 'code' => 'SRC']);
    $toStore = Store::create(['name' => 'Toko Tujuan', 'code' => 'DST']);
    $fromWarehouse = Warehouse::create(['store_id' => $fromStore->id, 'code' => 'SRC-WH', 'name' => 'Gudang Asal']);
    $toWarehouse = Warehouse::create(['store_id' => $toStore->id, 'code' => 'DST-WH', 'name' => 'Gudang Tujuan']);
    $category = ProductCategory::create(['name' => 'Sparepart', 'pricing_mode' => 'fixed']);
    $product = Product::create(['product_category_id' => $category->id, 'name' => 'Filter Oli', 'item_type' => 'part', 'has_variants' => false]);
    $variant = ProductVariant::create(['product_id' => $product->id, 'default_purchase_price' => 15000, 'is_active' => true]);

    return compact('user', 'fromStore', 'toStore', 'fromWarehouse', 'toWarehouse', 'variant');
}

test('stock transfer draft posts fifo batch movement between warehouses', function () {
    ['user' => $user, 'fromStore' => $fromStore, 'toStore' => $toStore, 'fromWarehouse' => $fromWarehouse, 'toWarehouse' => $toWarehouse, 'variant' => $variant] = stockTransferSetup();

    ProductStock::create(['product_variant_id' => $variant->id, 'warehouse_id' => $fromWarehouse->id, 'quantity' => 5]);
    $firstBatch = InventoryBatch::create(['product_variant_id' => $variant->id, 'warehouse_id' => $fromWarehouse->id, 'initial_quantity' => 2, 'current_quantity' => 2, 'unit_cost' => 1000, 'received_at' => now()->subDays(2)]);
    $secondBatch = InventoryBatch::create(['product_variant_id' => $variant->id, 'warehouse_id' => $fromWarehouse->id, 'initial_quantity' => 3, 'current_quantity' => 3, 'unit_cost' => 2000, 'received_at' => now()->subDay()]);

    $this->actingAs($user)
        ->post(route('stock-transfers.store'), [
            'from_store_id' => $fromStore->id,
            'to_store_id' => $toStore->id,
            'items' => [[
                'product_variant_id' => $variant->id,
                'from_warehouse_id' => $fromWarehouse->id,
                'to_warehouse_id' => $toWarehouse->id,
                'quantity' => 4,
                'unit_cost' => 9999,
            ]],
        ])
        ->assertRedirect();

    $transfer = StockTransfer::first();
    expect($transfer->status)->toBe('draft')
        ->and(ProductStock::where('warehouse_id', $toWarehouse->id)->count())->toBe(0);

    $this->actingAs($user)->post(route('stock-transfers.post', $transfer))->assertRedirect();

    expect($transfer->fresh()->status)->toBe('posted')
        ->and(ProductStock::where('warehouse_id', $fromWarehouse->id)->first()->quantity)->toBe(1)
        ->and(ProductStock::where('warehouse_id', $toWarehouse->id)->first()->quantity)->toBe(4)
        ->and($firstBatch->fresh()->current_quantity)->toBe(0)
        ->and($secondBatch->fresh()->current_quantity)->toBe(1)
        ->and(InventoryBatch::where('warehouse_id', $toWarehouse->id)->pluck('unit_cost')->map(fn ($value) => (float) $value)->all())->toBe([1000.0, 2000.0])
        ->and(InventoryMovement::where('reference_type', StockTransfer::class)->where('type', 'out')->count())->toBe(2)
        ->and(InventoryMovement::where('reference_type', StockTransfer::class)->where('type', 'in')->count())->toBe(2);
});

test('stock transfer rejects same store and insufficient source stock', function () {
    ['user' => $user, 'fromStore' => $fromStore, 'fromWarehouse' => $fromWarehouse, 'toWarehouse' => $toWarehouse, 'variant' => $variant] = stockTransferSetup();

    $this->actingAs($user)
        ->post(route('stock-transfers.store'), [
            'from_store_id' => $fromStore->id,
            'to_store_id' => $fromStore->id,
            'items' => [[
                'product_variant_id' => $variant->id,
                'from_warehouse_id' => $fromWarehouse->id,
                'to_warehouse_id' => $toWarehouse->id,
                'quantity' => 1,
            ]],
        ])
        ->assertSessionHasErrors('to_store_id');

    $transfer = StockTransfer::create(['from_store_id' => $fromStore->id, 'to_store_id' => Store::where('id', '!=', $fromStore->id)->first()->id, 'status' => 'draft', 'occurred_at' => now()]);
    $transfer->items()->create(['product_variant_id' => $variant->id, 'from_warehouse_id' => $fromWarehouse->id, 'to_warehouse_id' => $toWarehouse->id, 'quantity' => 3, 'unit_cost' => 1000]);

    $this->actingAs($user)->post(route('stock-transfers.post', $transfer))->assertSessionHasErrors('items');
    expect(ProductStock::count())->toBe(0);
});

test('posted stock transfer is immutable', function () {
    ['user' => $user, 'fromStore' => $fromStore, 'toStore' => $toStore] = stockTransferSetup();

    $transfer = StockTransfer::create(['from_store_id' => $fromStore->id, 'to_store_id' => $toStore->id, 'status' => 'posted', 'occurred_at' => now()]);

    $this->actingAs($user)->delete(route('stock-transfers.destroy', $transfer))->assertSessionHasErrors('status');
    expect($transfer->fresh())->not->toBeNull();
});
