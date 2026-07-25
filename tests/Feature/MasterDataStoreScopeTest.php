<?php

use App\Models\Attribute;
use App\Models\Brand;
use App\Models\CashFlowCategory;
use App\Models\DiscountType;
use App\Models\Payment;
use App\Models\ProductCategory;
use App\Models\Store;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('can create and query global and store-specific master data for all 7 master tables', function () {
    $user = User::factory()->create();
    $storeA = Store::create(['code' => 'T01', 'name' => 'Bengkel Surabaya']);
    $storeB = Store::create(['code' => 'T02', 'name' => 'Bengkel Jakarta']);

    // 1. Brand
    Brand::create(['name' => 'Global Brand', 'store_id' => null]);
    Brand::create(['name' => 'Local Brand A', 'store_id' => $storeA->id]);

    expect(Brand::forStore($storeA->id)->pluck('name')->all())->toContain('Global Brand', 'Local Brand A')
        ->and(Brand::forStore($storeB->id)->pluck('name')->all())->toContain('Global Brand')
        ->and(Brand::forStore($storeB->id)->pluck('name')->all())->not->toContain('Local Brand A');

    // 2. DiscountType
    DiscountType::create(['name' => 'Diskon Member Global', 'store_id' => null]);
    DiscountType::create(['name' => 'Diskon Promo Sby', 'store_id' => $storeA->id]);

    expect(DiscountType::forStore($storeA->id)->count())->toBe(2)
        ->and(DiscountType::forStore($storeB->id)->count())->toBe(1);

    // 3. Unit
    Unit::create(['name' => 'Pcs Global', 'symbol' => 'pcs', 'store_id' => null]);
    Unit::create(['name' => 'Set Sby', 'symbol' => 'set', 'store_id' => $storeA->id]);

    expect(Unit::forStore($storeA->id)->count())->toBe(2)
        ->and(Unit::forStore($storeB->id)->count())->toBe(1);

    // 4. Payment
    Payment::create(['name' => 'Cash Global', 'type' => 'cash', 'store_id' => null]);
    Payment::create(['name' => 'QRIS Sby', 'type' => 'qris', 'store_id' => $storeA->id]);

    expect(Payment::forStore($storeA->id)->count())->toBe(2)
        ->and(Payment::forStore($storeB->id)->count())->toBe(1);

    // 5. ProductCategory
    ProductCategory::create(['name' => 'Oli Global', 'pricing_mode' => 'fixed', 'store_id' => null]);
    ProductCategory::create(['name' => 'Sparepart Sby', 'pricing_mode' => 'fixed', 'store_id' => $storeA->id]);

    expect(ProductCategory::forStore($storeA->id)->count())->toBe(2)
        ->and(ProductCategory::forStore($storeB->id)->count())->toBe(1);

    // 6. Attribute
    Attribute::create(['name' => 'Warna Global', 'store_id' => null]);
    Attribute::create(['name' => 'Ukuran Sby', 'store_id' => $storeA->id]);

    expect(Attribute::forStore($storeA->id)->count())->toBe(2)
        ->and(Attribute::forStore($storeB->id)->count())->toBe(1);

    // 7. CashFlowCategory
    CashFlowCategory::create(['name' => 'Gaji Global', 'type' => 'expense', 'store_id' => null]);
    CashFlowCategory::create(['name' => 'Operasional Sby', 'type' => 'expense', 'store_id' => $storeA->id]);

    expect(CashFlowCategory::forStore($storeA->id)->count())->toBe(2)
        ->and(CashFlowCategory::forStore($storeB->id)->count())->toBe(1);
});

test('controller CRUD HTTP requests support store_id field', function () {
    $user = User::factory()->create();
    $store = Store::create(['code' => 'T01', 'name' => 'Bengkel Test']);

    // Test POST to BrandController with store_id
    $this->actingAs($user)
        ->post(route('brands.store'), [
            'name' => 'Brand Cabang Test',
            'store_id' => $store->id,
        ])
        ->assertRedirect(route('brands.index'));

    $brand = Brand::where('name', 'Brand Cabang Test')->firstOrFail();
    expect($brand->store_id)->toBe($store->id);

    // Test PUT to BrandController to clear store_id (make global)
    $this->actingAs($user)
        ->put(route('brands.update', $brand), [
            'name' => 'Brand Cabang Test Updated',
            'store_id' => null,
        ])
        ->assertRedirect(route('brands.index'));

    expect($brand->refresh()->store_id)->toBeNull();
});
