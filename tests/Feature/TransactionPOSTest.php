<?php

use App\Models\DiscountType;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductDiscount;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Models\ServiceOrder;
use App\Models\Store;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('can access cashier pos terminal and create retail transaction with stock deduction', function () {
    $user = User::factory()->create();
    $store = Store::create(['name' => 'Bengkel Utama', 'code' => 'BKL-01']);
    $category = ProductCategory::create(['name' => 'Oli', 'pricing_mode' => 'fixed']);

    $product = Product::create([
        'product_category_id' => $category->id,
        'name' => 'Oli MPX2 Matic',
        'item_type' => 'part',
        'has_variants' => false,
    ]);

    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'default_selling_price' => 55000,
        'is_active' => true,
    ]);

    $warehouse = Warehouse::create([
        'store_id' => $store->id,
        'code' => 'GDG-01',
        'name' => 'Gudang Utama',
    ]);

    $stock = ProductStock::create([
        'product_variant_id' => $variant->id,
        'warehouse_id' => $warehouse->id,
        'quantity' => 10,
    ]);

    // 1. Access Cashier POS page
    $this->actingAs($user)
        ->get(route('transactions.create'))
        ->assertOk();

    // 2. Submit Retail Transaction
    $this->actingAs($user)
        ->post(route('transactions.store'), [
            'store_id' => $store->id,
            'type' => 'retail',
            'paid_amount' => 60000,
            'items' => [
                [
                    'item_type' => 'part',
                    'product_variant_id' => $variant->id,
                    'description' => 'Oli MPX2 Matic',
                    'quantity' => 2,
                    'unit_price' => 55000,
                ],
            ],
        ])
        ->assertRedirect();

    expect(Transaction::count())->toBe(1);
    $tx = Transaction::first();

    expect($tx->type)->toBe('retail')
        ->and((float) $tx->subtotal)->toBe(110000.0)
        ->and((float) $tx->grand_total)->toBe(110000.0)
        ->and((float) $tx->paid_amount)->toBe(60000.0);

    // Verify Stock Deducted by 2 (10 - 2 = 8)
    expect($stock->fresh()->quantity)->toBe(8);
});

test('service order settlement in POS cashier updates service order status to invoiced', function () {
    $user = User::factory()->create();
    $store = Store::create(['name' => 'Bengkel Utama', 'code' => 'BKL-01']);

    $serviceOrder = ServiceOrder::create([
        'number' => 'SO-20260729-0005',
        'store_id' => $store->id,
        'customer_name' => 'Budi Santoso',
        'plate_number' => 'B 1234 ABC',
        'status' => 'ready',
        'checkin_at' => now(),
        'estimated_total' => 100000,
    ]);

    $this->actingAs($user)
        ->post(route('transactions.store'), [
            'store_id' => $store->id,
            'type' => 'service',
            'service_order_id' => $serviceOrder->id,
            'paid_amount' => 100000,
            'items' => [
                [
                    'item_type' => 'labor',
                    'description' => 'Jasa Servis Karburator',
                    'quantity' => 1,
                    'unit_price' => 100000,
                ],
            ],
        ])
        ->assertRedirect();

    expect(Transaction::count())->toBe(1);
    $tx = Transaction::first();

    $serviceOrder->refresh();
    expect($serviceOrder->status)->toBe('invoiced')
        ->and($serviceOrder->transaction_id)->toBe($tx->id);

    // Can access print page
    $this->actingAs($user)
        ->get(route('transactions.print', $tx->id))
        ->assertOk();
});

test('retail transaction applies product discount rule and discount_type_id', function () {
    $user = User::factory()->create();
    $store = Store::create(['name' => 'Bengkel Utama', 'code' => 'BKL-01']);
    $category = ProductCategory::create(['name' => 'Sparepart', 'pricing_mode' => 'fixed']);

    $product = Product::create([
        'product_category_id' => $category->id,
        'name' => 'Kampas Rem',
        'item_type' => 'part',
        'has_variants' => false,
    ]);

    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'default_selling_price' => 100000,
        'is_active' => true,
    ]);

    $discountType = DiscountType::create([
        'name' => 'Promo P1',
        'description' => 'Diskon Promo 10%',
    ]);

    ProductDiscount::create([
        'product_variant_id' => $variant->id,
        'store_id' => $store->id,
        'discount_type_id' => $discountType->id,
        'type' => 'percent',
        'value' => 10,
    ]);

    $this->actingAs($user)
        ->post(route('transactions.store'), [
            'store_id' => $store->id,
            'type' => 'retail',
            'paid_amount' => 90000,
            'items' => [
                [
                    'item_type' => 'part',
                    'product_variant_id' => $variant->id,
                    'description' => 'Kampas Rem',
                    'quantity' => 1,
                    'unit_price' => 100000,
                    'discount_type_id' => $discountType->id,
                    'item_discount_mode' => 'percent',
                    'item_discount_value' => 10,
                ],
            ],
        ])
        ->assertRedirect();

    expect(Transaction::count())->toBe(1);
    $tx = Transaction::first();

    expect((float) $tx->subtotal)->toBe(100000.0)
        ->and((float) $tx->item_discount_total)->toBe(10000.0)
        ->and((float) $tx->grand_total)->toBe(90000.0);

    $item = $tx->items->first();
    expect($item->discount_type_id)->toBe($discountType->id)
        ->and((float) $item->item_discount_amount)->toBe(10000.0)
        ->and((float) $item->line_total)->toBe(90000.0);
});
