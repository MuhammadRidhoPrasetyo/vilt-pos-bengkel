<?php

use App\Models\InventoryBatch;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Models\StockOpname;
use App\Models\Store;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function stockOpnameSetup(): array
{
    $user = User::factory()->create();
    $store = Store::create(['name' => 'Bengkel Utama', 'code' => 'BKL-01']);
    $warehouse = Warehouse::create(['store_id' => $store->id, 'code' => 'GDG-01', 'name' => 'Gudang Sparepart']);
    $category = ProductCategory::create(['name' => 'Sparepart', 'pricing_mode' => 'fixed']);
    $product = Product::create(['product_category_id' => $category->id, 'name' => 'Kampas Rem Depan', 'item_type' => 'part', 'has_variants' => false]);
    $variant = ProductVariant::create(['product_id' => $product->id, 'default_purchase_price' => 25000, 'is_active' => true]);

    return compact('user', 'store', 'warehouse', 'variant');
}

test('stock opname draft can be created and posted with stock increase', function () {
    ['user' => $user, 'store' => $store, 'warehouse' => $warehouse, 'variant' => $variant] = stockOpnameSetup();

    // Inisiasi stok awal sistem = 10 pcs
    ProductStock::create([
        'product_variant_id' => $variant->id,
        'warehouse_id' => $warehouse->id,
        'quantity' => 10,
        'minimum_stock' => 2,
    ]);

    // Staff menghitung fisik = 14 pcs (selisih lebih +4)
    $this->actingAs($user)
        ->post(route('stock-opnames.store'), [
            'store_id' => $store->id,
            'warehouse_id' => $warehouse->id,
            'occurred_at' => now()->toDateString(),
            'notes' => 'Opname Fisik Akhir Bulan',
            'items' => [[
                'product_variant_id' => $variant->id,
                'system_quantity' => 10,
                'physical_quantity' => 14,
                'unit_cost' => 25000,
                'note' => 'Ditemukan kelebihan 4 pcs',
            ]],
        ])
        ->assertRedirect();

    $opname = StockOpname::with('items')->first();
    expect($opname)->not->toBeNull()
        ->and($opname->status)->toBe('draft')
        ->and($opname->total_system_qty)->toBe(10)
        ->and($opname->total_physical_qty)->toBe(14)
        ->and($opname->total_difference_qty)->toBe(4)
        ->and((float) $opname->total_difference_value)->toBe(100000.0);

    // Saat draft, stok fisik belum berubah
    expect(ProductStock::first()->quantity)->toBe(10);

    // Posting stock opname
    $this->actingAs($user)
        ->post(route('stock-opnames.post', $opname))
        ->assertRedirect(route('stock-opnames.show', $opname));

    expect($opname->fresh()->status)->toBe('posted')
        ->and(ProductStock::first()->quantity)->toBe(14)
        ->and((float) InventoryBatch::first()->unit_cost)->toBe(25000.0)
        ->and((int) InventoryBatch::first()->current_quantity)->toBe(4)
        ->and(InventoryMovement::first()->type)->toBe('in')
        ->and(InventoryMovement::first()->reference_type)->toBe(StockOpname::class);
});

test('stock opname posting with stock decrease deducts fifo batches', function () {
    ['user' => $user, 'store' => $store, 'warehouse' => $warehouse, 'variant' => $variant] = stockOpnameSetup();

    // Stok awal = 10 pcs dengan batch FIFO
    ProductStock::create([
        'product_variant_id' => $variant->id,
        'warehouse_id' => $warehouse->id,
        'quantity' => 10,
        'minimum_stock' => 2,
    ]);

    $batch = InventoryBatch::create([
        'product_variant_id' => $variant->id,
        'warehouse_id' => $warehouse->id,
        'initial_quantity' => 10,
        'current_quantity' => 10,
        'unit_cost' => 25000,
        'received_at' => now()->subDays(5),
    ]);

    // Hasil hitung fisik = 7 pcs (selisih kurang -3 pcs)
    $this->actingAs($user)
        ->post(route('stock-opnames.store'), [
            'store_id' => $store->id,
            'warehouse_id' => $warehouse->id,
            'notes' => 'Opname minus 3 pcs',
            'items' => [[
                'product_variant_id' => $variant->id,
                'system_quantity' => 10,
                'physical_quantity' => 7,
                'unit_cost' => 25000,
                'note' => '3 pcs rusak',
            ]],
        ])
        ->assertRedirect();

    $opname = StockOpname::first();
    expect($opname->total_difference_qty)->toBe(-3);

    $this->actingAs($user)
        ->post(route('stock-opnames.post', $opname))
        ->assertRedirect();

    expect($opname->fresh()->status)->toBe('posted')
        ->and(ProductStock::first()->quantity)->toBe(7)
        ->and($batch->fresh()->current_quantity)->toBe(7)
        ->and(InventoryMovement::first()->type)->toBe('out')
        ->and(InventoryMovement::first()->quantity)->toBe(3)
        ->and(InventoryMovement::first()->reference_type)->toBe(StockOpname::class);
});

test('stock opname draft can be cancelled and cannot be posted after cancellation', function () {
    ['user' => $user, 'store' => $store, 'warehouse' => $warehouse, 'variant' => $variant] = stockOpnameSetup();

    $opname = StockOpname::create([
        'store_id' => $store->id,
        'warehouse_id' => $warehouse->id,
        'opname_number' => 'SO/202610/0001',
        'status' => 'draft',
        'created_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->post(route('stock-opnames.cancel', $opname))
        ->assertRedirect(route('stock-opnames.show', $opname));

    expect($opname->fresh()->status)->toBe('cancelled');

    $this->actingAs($user)
        ->post(route('stock-opnames.post', $opname))
        ->assertSessionHasErrors('status');
});

test('warehouse stock endpoint returns current stock products for counting sheet', function () {
    ['user' => $user, 'store' => $store, 'warehouse' => $warehouse, 'variant' => $variant] = stockOpnameSetup();

    ProductStock::create([
        'product_variant_id' => $variant->id,
        'warehouse_id' => $warehouse->id,
        'quantity' => 15,
        'minimum_stock' => 0,
    ]);

    $response = $this->actingAs($user)
        ->getJson(route('stock-opnames.warehouse-stock', $warehouse));

    $response->assertOk()
        ->assertJsonPath('warehouse_id', $warehouse->id)
        ->assertJsonPath('items.0.product_variant_id', $variant->id)
        ->assertJsonPath('items.0.system_quantity', 15);
});

test('stock opname draft can be updated with modified physical counts', function () {
    ['user' => $user, 'store' => $store, 'warehouse' => $warehouse, 'variant' => $variant] = stockOpnameSetup();

    $this->actingAs($user)
        ->post(route('stock-opnames.store'), [
            'store_id' => $store->id,
            'warehouse_id' => $warehouse->id,
            'notes' => 'Awal',
            'items' => [[
                'product_variant_id' => $variant->id,
                'system_quantity' => 10,
                'physical_quantity' => 10,
                'unit_cost' => 20000,
            ]],
        ]);

    $opname = StockOpname::first();
    expect($opname->total_difference_qty)->toBe(0);

    $this->actingAs($user)
        ->put(route('stock-opnames.update', $opname), [
            'notes' => 'Diperbarui setelah hitung ulang',
            'items' => [[
                'product_variant_id' => $variant->id,
                'system_quantity' => 10,
                'physical_quantity' => 12,
                'unit_cost' => 20000,
                'note' => 'Ada 2 pcs terselip di bawah rak',
            ]],
        ])
        ->assertRedirect(route('stock-opnames.show', $opname));

    expect($opname->fresh()->total_difference_qty)->toBe(2)
        ->and((float) $opname->fresh()->total_difference_value)->toBe(40000.0)
        ->and($opname->fresh()->notes)->toBe('Diperbarui setelah hitung ulang');
});

test('stock opname draft can be deleted', function () {
    ['user' => $user, 'store' => $store, 'warehouse' => $warehouse, 'variant' => $variant] = stockOpnameSetup();

    $opname = StockOpname::create([
        'store_id' => $store->id,
        'warehouse_id' => $warehouse->id,
        'status' => 'draft',
        'created_by' => $user->id,
    ]);

    $this->actingAs($user)
        ->delete(route('stock-opnames.destroy', $opname))
        ->assertRedirect(route('stock-opnames.index'));

    expect(StockOpname::count())->toBe(0);
});
