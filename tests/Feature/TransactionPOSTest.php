<?php

use App\Models\DiscountType;
use App\Models\InventoryBatch;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductDiscount;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Models\ServiceOrder;
use App\Models\Store;
use App\Models\Transaction;
use App\Models\TransactionItemBatch;
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

test('partial transaction can be settled with payment attempts including overpayment change', function () {
    $user = User::factory()->create();
    $store = Store::create(['name' => 'Bengkel Utama', 'code' => 'BKL-01']);
    $payment = Payment::create(['name' => 'Tunai', 'type' => 'cash']);
    $category = ProductCategory::create(['name' => 'Sparepart', 'pricing_mode' => 'fixed']);
    $product = Product::create([
        'product_category_id' => $category->id,
        'name' => 'Busi',
        'item_type' => 'part',
        'has_variants' => false,
    ]);
    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'default_selling_price' => 100000,
        'is_active' => true,
    ]);
    $warehouse = Warehouse::create(['store_id' => $store->id, 'code' => 'GDG-01', 'name' => 'Gudang Utama']);

    ProductStock::create(['product_variant_id' => $variant->id, 'warehouse_id' => $warehouse->id, 'quantity' => 5]);

    $this->actingAs($user)
        ->post(route('transactions.store'), [
            'store_id' => $store->id,
            'payment_id' => $payment->id,
            'type' => 'retail',
            'paid_amount' => 40000,
            'items' => [
                [
                    'item_type' => 'part',
                    'product_variant_id' => $variant->id,
                    'description' => 'Busi',
                    'quantity' => 1,
                    'unit_price' => 100000,
                ],
            ],
        ])
        ->assertRedirect();

    $transaction = Transaction::with('paymentAttempts')->first();
    expect($transaction->payment_status)->toBe('partial')
        ->and((float) $transaction->paid_amount)->toBe(40000.0)
        ->and($transaction->paymentAttempts)->toHaveCount(1);

    $this->actingAs($user)
        ->post(route('transactions.payment-attempts.store', $transaction), [
            'payment_id' => $payment->id,
            'amount_given' => 70000,
            'note' => 'Pelunasan',
        ])
        ->assertRedirect(route('transactions.show', $transaction->id));

    $transaction->refresh()->load('paymentAttempts');

    expect($transaction->payment_status)->toBe('paid')
        ->and((float) $transaction->paid_amount)->toBe(110000.0)
        ->and((float) $transaction->change_amount)->toBe(10000.0)
        ->and($transaction->paymentAttempts)->toHaveCount(2);

    $attempt = $transaction->paymentAttempts->last();
    expect((float) $attempt->amount)->toBe(60000.0)
        ->and((float) $attempt->amount_given)->toBe(70000.0)
        ->and((float) $attempt->change)->toBe(10000.0)
        ->and($attempt->metadata['note'])->toBe('Pelunasan');
});

test('retail transaction allocates inventory batches with fifo costing and restores them on delete', function () {
    $user = User::factory()->create();
    $store = Store::create(['name' => 'Bengkel Utama', 'code' => 'BKL-01']);
    $category = ProductCategory::create(['name' => 'Oli', 'pricing_mode' => 'fixed']);
    $product = Product::create([
        'product_category_id' => $category->id,
        'name' => 'Oli Mesin',
        'item_type' => 'part',
        'has_variants' => false,
    ]);
    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'default_selling_price' => 5000,
        'is_active' => true,
    ]);
    $warehouse = Warehouse::create(['store_id' => $store->id, 'code' => 'GDG-01', 'name' => 'Gudang Utama']);
    $stock = ProductStock::create(['product_variant_id' => $variant->id, 'warehouse_id' => $warehouse->id, 'quantity' => 5]);
    $firstBatch = InventoryBatch::create([
        'product_variant_id' => $variant->id,
        'warehouse_id' => $warehouse->id,
        'initial_quantity' => 2,
        'current_quantity' => 2,
        'unit_cost' => 1000,
        'received_at' => now()->subDays(2),
    ]);
    $secondBatch = InventoryBatch::create([
        'product_variant_id' => $variant->id,
        'warehouse_id' => $warehouse->id,
        'initial_quantity' => 3,
        'current_quantity' => 3,
        'unit_cost' => 2000,
        'received_at' => now()->subDay(),
    ]);

    $this->actingAs($user)
        ->post(route('transactions.store'), [
            'store_id' => $store->id,
            'type' => 'retail',
            'paid_amount' => 20000,
            'items' => [
                [
                    'item_type' => 'part',
                    'product_variant_id' => $variant->id,
                    'description' => 'Oli Mesin',
                    'quantity' => 4,
                    'unit_price' => 5000,
                ],
            ],
        ])
        ->assertRedirect();

    $transaction = Transaction::with('items.batches')->first();
    $item = $transaction->items->first();

    expect(TransactionItemBatch::count())->toBe(2)
        ->and($item->batches->pluck('quantity')->all())->toBe([2, 2])
        ->and((float) $item->line_cost_total)->toBe(6000.0)
        ->and((float) $item->line_profit)->toBe(14000.0)
        ->and((float) $transaction->total_cost)->toBe(6000.0)
        ->and((float) $transaction->total_profit)->toBe(14000.0)
        ->and($stock->fresh()->quantity)->toBe(1)
        ->and($firstBatch->fresh()->current_quantity)->toBe(0)
        ->and($secondBatch->fresh()->current_quantity)->toBe(1);

    $this->actingAs($user)
        ->delete(route('transactions.destroy', $transaction))
        ->assertRedirect(route('transactions.index'));

    expect($stock->fresh()->quantity)->toBe(5)
        ->and($firstBatch->fresh()->current_quantity)->toBe(2)
        ->and($secondBatch->fresh()->current_quantity)->toBe(3);
});

test('transaction show page includes payment attempts and item batches props', function () {
    $user = User::factory()->create();
    $store = Store::create(['name' => 'Bengkel Utama', 'code' => 'BKL-01']);
    $category = ProductCategory::create(['name' => 'Sparepart', 'pricing_mode' => 'fixed']);
    $product = Product::create([
        'product_category_id' => $category->id,
        'name' => 'Filter Udara',
        'item_type' => 'part',
        'has_variants' => false,
    ]);
    $variant = ProductVariant::create([
        'product_id' => $product->id,
        'default_selling_price' => 25000,
        'is_active' => true,
    ]);
    $warehouse = Warehouse::create(['store_id' => $store->id, 'code' => 'GDG-01', 'name' => 'Gudang Utama']);
    ProductStock::create(['product_variant_id' => $variant->id, 'warehouse_id' => $warehouse->id, 'quantity' => 2]);
    InventoryBatch::create([
        'product_variant_id' => $variant->id,
        'warehouse_id' => $warehouse->id,
        'initial_quantity' => 2,
        'current_quantity' => 2,
        'unit_cost' => 12000,
        'received_at' => now(),
    ]);

    $this->actingAs($user)
        ->post(route('transactions.store'), [
            'store_id' => $store->id,
            'type' => 'retail',
            'paid_amount' => 10000,
            'items' => [
                [
                    'item_type' => 'part',
                    'product_variant_id' => $variant->id,
                    'description' => 'Filter Udara',
                    'quantity' => 1,
                    'unit_price' => 25000,
                ],
            ],
        ]);

    $transaction = Transaction::first();

    $this->actingAs($user)
        ->get(route('transactions.show', $transaction))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('transactions/show')
            ->has('transaction.data.payment_attempts', 1)
            ->has('transaction.data.items.0.batches', 1)
            ->where('transaction.data.outstanding_amount', 15000)
        );
});

test('staff with assigned store is locked to assigned store on cashier page and payload', function () {
    $storeA = Store::create(['name' => 'Store A', 'code' => 'STA']);
    $storeB = Store::create(['name' => 'Store B', 'code' => 'STB']);

    $staff = User::factory()->create(['store_id' => $storeA->id]);

    $category = ProductCategory::create(['name' => 'Service', 'pricing_mode' => 'fixed']);
    $product = Product::create(['product_category_id' => $category->id, 'name' => 'Jasa Servis', 'item_type' => 'labor']);
    $variant = ProductVariant::create(['product_id' => $product->id, 'default_selling_price' => 50000, 'is_active' => true]);

    // 1. Staff accessing POS page receives locked store props
    $this->actingAs($staff)
        ->get(route('transactions.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('activeStoreId', $storeA->id)
            ->where('isStoreLocked', true)
        );

    // 2. Staff attempting to submit payload with storeB will have store_id sanitized to storeA
    $this->actingAs($staff)
        ->post(route('transactions.store'), [
            'store_id' => $storeB->id,
            'type' => 'retail',
            'paid_amount' => 50000,
            'items' => [
                [
                    'item_type' => 'labor',
                    'product_variant_id' => $variant->id,
                    'description' => 'Jasa Servis',
                    'quantity' => 1,
                    'unit_price' => 50000,
                ],
            ],
        ])
        ->assertRedirect();

    $transaction = Transaction::first();
    expect($transaction->store_id)->toBe($storeA->id);
});
