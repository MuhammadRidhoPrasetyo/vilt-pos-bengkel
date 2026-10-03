<?php

use App\Models\Partner;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Models\Purchase;
use App\Models\ServiceOrder;
use App\Models\Store;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\PurchaseService;
use App\Services\ServiceOrderService;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->store = Store::create([
        'name' => 'Store SoftDelete Test',
        'code' => 'SDT',
        'address' => 'Jl. Test No. 1',
    ]);

    $this->user = User::factory()->create([
        'store_id' => $this->store->id,
    ]);

    $this->customer = Partner::create([
        'store_id' => $this->store->id,
        'code' => 'CUST-SDT-001',
        'kind' => 'person',
        'name' => 'Customer SoftDelete',
        'phone' => '08123456789',
    ]);

    $this->category = ProductCategory::create([
        'name' => 'Sparepart',
    ]);

    $this->product = Product::create([
        'product_category_id' => $this->category->id,
        'name' => 'Kampas Rem Depan',
        'item_type' => 'part',
    ]);

    $this->variant = ProductVariant::create([
        'product_id' => $this->product->id,
        'sku' => 'VAR-SDT-001',
        'default_selling_price' => 75000,
        'default_purchase_price' => 50000,
    ]);

    $this->warehouse = Warehouse::create([
        'store_id' => $this->store->id,
        'code' => 'GDG-SDT',
        'name' => 'Gudang SoftDelete Test',
    ]);

    $this->productStock = ProductStock::create([
        'product_variant_id' => $this->variant->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 10,
    ]);

    $this->payment = Payment::create([
        'name' => 'Cash',
        'type' => 'cash',
    ]);
});

test('transaction and its items can be soft deleted and preserved with trashed queries', function () {
    $txService = app(TransactionService::class);

    $tx1 = $txService->create([
        'store_id' => $this->store->id,
        'customer_id' => $this->customer->id,
        'payment_id' => $this->payment->id,
        'type' => 'retail',
        'items' => [
            [
                'product_variant_id' => $this->variant->id,
                'quantity' => 2,
                'unit_price' => 75000,
            ],
        ],
    ], $this->user->id);

    expect(Transaction::count())->toBe(1);
    expect($tx1->items()->count())->toBe(1);
    $firstNumber = $tx1->number;

    // Soft delete transaction
    $txService->delete($tx1);

    // Active count is 0
    expect(Transaction::count())->toBe(0);
    expect($tx1->fresh()->trashed())->toBeTrue();
    expect($tx1->items()->count())->toBe(0);
    expect($tx1->items()->withTrashed()->count())->toBe(1);

    // Document numbering continues monotonically without collision
    $tx2 = $txService->create([
        'store_id' => $this->store->id,
        'customer_id' => $this->customer->id,
        'payment_id' => $this->payment->id,
        'type' => 'retail',
        'items' => [
            [
                'product_variant_id' => $this->variant->id,
                'quantity' => 1,
                'unit_price' => 75000,
            ],
        ],
    ], $this->user->id);

    expect(Transaction::count())->toBe(1);
    expect(Transaction::withTrashed()->count())->toBe(2);
    expect($tx2->number)->not->toBe($firstNumber);
});

test('historical relations on transaction resolve correctly even when master entities are soft deleted', function () {
    $txService = app(TransactionService::class);

    $tx = $txService->create([
        'store_id' => $this->store->id,
        'customer_id' => $this->customer->id,
        'payment_id' => $this->payment->id,
        'type' => 'retail',
        'items' => [
            [
                'product_variant_id' => $this->variant->id,
                'quantity' => 1,
                'unit_price' => 75000,
            ],
        ],
    ], $this->user->id);

    // Soft delete related master entities
    $this->customer->delete();
    $this->store->delete();
    $this->user->delete();
    $this->product->delete();
    $this->variant->delete();

    // Verify entities are trashed
    expect($this->customer->fresh()->trashed())->toBeTrue();
    expect($this->store->fresh()->trashed())->toBeTrue();
    expect($this->user->fresh()->trashed())->toBeTrue();
    expect($this->product->fresh()->trashed())->toBeTrue();
    expect($this->variant->fresh()->trashed())->toBeTrue();

    // Query transaction and verify withTrashed relationships still resolve
    $freshTx = Transaction::with(['customer', 'store', 'user', 'items.productVariant.product'])->find($tx->id);

    expect($freshTx->customer)->not->toBeNull();
    expect($freshTx->customer->name)->toBe('Customer SoftDelete');
    expect($freshTx->store)->not->toBeNull();
    expect($freshTx->store->code)->toBe('SDT');
    expect($freshTx->user)->not->toBeNull();
    expect($freshTx->items->first()->productVariant)->not->toBeNull();
    expect($freshTx->items->first()->productVariant->product)->not->toBeNull();
    expect($freshTx->items->first()->productVariant->product->name)->toBe('Kampas Rem Depan');
});

test('service order and its items can be soft deleted', function () {
    $service = app(ServiceOrderService::class);

    $order = $service->create([
        'store_id' => $this->store->id,
        'customer_id' => $this->customer->id,
        'customer_name' => 'Budi Customer',
        'customer_phone' => '081299998888',
        'plate_number' => 'B 1234 XYZ',
        'vehicle_brand' => 'Honda',
        'vehicle_model' => 'Vario 150',
        'general_complaint' => 'Tarikan berat',
        'items' => [
            [
                'item_type' => 'labor',
                'description' => 'Servis Ringan CVT',
                'quantity' => 1,
                'unit_price' => 50000,
            ],
        ],
    ]);

    expect(ServiceOrder::count())->toBe(1);
    expect($order->items()->count())->toBe(1);

    $service->delete($order);

    expect(ServiceOrder::count())->toBe(0);
    expect(ServiceOrder::withTrashed()->count())->toBe(1);
    expect($order->fresh()->trashed())->toBeTrue();
    expect($order->items()->withTrashed()->count())->toBe(1);
});

test('purchase and its items can be soft deleted and preserved with trashed', function () {
    $service = app(PurchaseService::class);

    $supplier = Partner::create([
        'store_id' => $this->store->id,
        'code' => 'SUPP-SDT-001',
        'kind' => 'organization',
        'name' => 'Supplier Utama',
    ]);

    $purchase = $service->create([
        'store_id' => $this->store->id,
        'supplier_id' => $supplier->id,
        'purchase_date' => now()->toDateString(),
        'items' => [
            [
                'product_variant_id' => $this->variant->id,
                'quantity_ordered' => 5,
                'unit_purchase_price' => 50000,
                'warehouse_id' => $this->warehouse->id,
            ],
        ],
    ], $this->user->id);

    expect(Purchase::count())->toBe(1);
    expect($purchase->items()->count())->toBe(1);

    $service->delete($purchase);

    expect(Purchase::count())->toBe(0);
    expect(Purchase::withTrashed()->count())->toBe(1);
    expect($purchase->fresh()->trashed())->toBeTrue();
    expect($purchase->items()->withTrashed()->count())->toBe(1);
});
