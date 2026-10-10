<?php

use App\Models\Partner;
use App\Models\Payment;
use App\Models\ServiceOrder;
use App\Models\ServiceOrderItem;
use App\Models\Store;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Seed or get essential roles and permissions for tests
    Role::findOrCreate('owner', 'web');
    Role::findOrCreate('kasir', 'web');
    Role::findOrCreate('mekanik', 'web');

    Permission::findOrCreate('reports.view', 'web');
    Permission::findOrCreate('reports.staff.view', 'web');
});

test('unauthenticated user is redirected to login', function () {
    $response = $this->get('/reports/staff-performance');

    $response->assertRedirect('/login');
});

test('user without reports.staff.view permission gets 403 forbidden', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/reports/staff-performance');

    $response->assertStatus(403);
});

test('user with reports.staff.view permission can view staff performance page', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('reports.staff.view');

    $response = $this->actingAs($user)->get('/reports/staff-performance');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('reports/staff-performance'));
});

test('report accurately calculates cashier transactions and total sales within date range', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('reports.staff.view');

    $store = Store::create([
        'code' => 'TKO-01',
        'name' => 'Bengkel Pusat',
    ]);

    $kasir = User::factory()->create([
        'name' => 'Budi Kasir',
        'store_id' => $store->id,
    ]);
    $kasir->assignRole('kasir');

    $customer = Partner::create([
        'code' => 'CUST-001',
        'name' => 'Pelanggan Setia',
        'kind' => 'person',
    ]);

    $payment = Payment::create([
        'name' => 'Tunai',
        'type' => 'cash',
        'is_active' => true,
    ]);

    // Transaction in range (today)
    Transaction::create([
        'number' => 'TRX-001',
        'store_id' => $store->id,
        'user_id' => $kasir->id,
        'customer_id' => $customer->id,
        'payment_id' => $payment->id,
        'transaction_date' => Carbon::now(),
        'type' => 'retail',
        'subtotal' => 150000,
        'grand_total' => 150000,
        'paid_amount' => 150000,
        'status' => 'completed',
    ]);

    // Another transaction in range
    Transaction::create([
        'number' => 'TRX-002',
        'store_id' => $store->id,
        'user_id' => $kasir->id,
        'customer_id' => $customer->id,
        'payment_id' => $payment->id,
        'transaction_date' => Carbon::now(),
        'type' => 'retail',
        'subtotal' => 250000,
        'grand_total' => 250000,
        'paid_amount' => 250000,
        'status' => 'completed',
    ]);

    // Transaction outside range (2 months ago)
    Transaction::create([
        'number' => 'TRX-OLD',
        'store_id' => $store->id,
        'user_id' => $kasir->id,
        'customer_id' => $customer->id,
        'payment_id' => $payment->id,
        'transaction_date' => Carbon::now()->subMonths(2),
        'type' => 'retail',
        'subtotal' => 500000,
        'grand_total' => 500000,
        'paid_amount' => 500000,
        'status' => 'completed',
    ]);

    $response = $this->actingAs($user)->get('/reports/staff-performance?start_date='.Carbon::now()->startOfMonth()->toDateString().'&end_date='.Carbon::now()->toDateString());

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('reports/staff-performance')
        ->has('cashiers', 1)
        ->where('cashiers.0.name', 'Budi Kasir')
        ->where('cashiers.0.total_transactions', 2)
        ->where('cashiers.0.total_sales', 400000)
        ->where('summary.total_cashier_sales', 400000)
        ->where('summary.total_cashier_transactions', 2)
    );
});

test('report accurately calculates mechanic service jobs within date range', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('reports.staff.view');

    $store = Store::create([
        'code' => 'TKO-01',
        'name' => 'Bengkel Pusat',
    ]);

    $mekanik = User::factory()->create([
        'name' => 'Joko Mekanik',
        'store_id' => $store->id,
    ]);
    $mekanik->assignRole('mekanik');

    $customer = Partner::create([
        'code' => 'CUST-002',
        'name' => 'Pelanggan Mobil',
        'kind' => 'person',
    ]);

    $serviceOrder = ServiceOrder::create([
        'number' => 'SPK-001',
        'store_id' => $store->id,
        'customer_id' => $customer->id,
        'status' => 'ready',
        'plate_number' => 'B 1234 ABC',
        'vehicle_brand' => 'Honda',
        'vehicle_model' => 'Vario',
        'checkin_at' => Carbon::now(),
    ]);

    ServiceOrderItem::create([
        'service_order_id' => $serviceOrder->id,
        'mechanic_id' => $mekanik->id,
        'item_type' => 'service',
        'description' => 'Ganti Oli & Tune Up',
        'quantity' => 1,
        'unit_price' => 85000,
        'line_total' => 85000,
        'assigned_at' => Carbon::now(),
    ]);

    ServiceOrderItem::create([
        'service_order_id' => $serviceOrder->id,
        'mechanic_id' => $mekanik->id,
        'item_type' => 'service',
        'description' => 'Servis CVT',
        'quantity' => 1,
        'unit_price' => 65000,
        'line_total' => 65000,
        'assigned_at' => Carbon::now(),
    ]);

    $response = $this->actingAs($user)->get('/reports/staff-performance?start_date='.Carbon::now()->startOfMonth()->toDateString().'&end_date='.Carbon::now()->toDateString());

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('reports/staff-performance')
        ->has('mechanics', 1)
        ->where('mechanics.0.name', 'Joko Mekanik')
        ->where('mechanics.0.total_jobs', 2)
        ->where('mechanics.0.total_vehicles', 1)
        ->where('mechanics.0.total_revenue', 150000)
        ->where('summary.total_mechanic_revenue', 150000)
        ->where('summary.total_mechanic_jobs', 2)
    );
});

test('cashier and mechanic detail modal endpoints return json data', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('reports.staff.view');

    $store = Store::create(['code' => 'TKO-01', 'name' => 'Bengkel Pusat']);
    $kasir = User::factory()->create(['name' => 'Budi Kasir', 'store_id' => $store->id]);
    $mekanik = User::factory()->create(['name' => 'Joko Mekanik', 'store_id' => $store->id]);

    $customer = Partner::create(['code' => 'CUST-003', 'name' => 'Pelanggan', 'kind' => 'person']);
    $payment = Payment::create(['name' => 'Tunai', 'type' => 'cash', 'is_active' => true]);

    Transaction::create([
        'number' => 'TRX-101',
        'store_id' => $store->id,
        'user_id' => $kasir->id,
        'customer_id' => $customer->id,
        'payment_id' => $payment->id,
        'transaction_date' => Carbon::now(),
        'type' => 'retail',
        'subtotal' => 100000,
        'grand_total' => 100000,
        'paid_amount' => 100000,
        'status' => 'completed',
    ]);

    $serviceOrder = ServiceOrder::create([
        'number' => 'SPK-101',
        'store_id' => $store->id,
        'customer_id' => $customer->id,
        'status' => 'ready',
        'plate_number' => 'B 9999 XYZ',
        'checkin_at' => Carbon::now(),
    ]);

    ServiceOrderItem::create([
        'service_order_id' => $serviceOrder->id,
        'mechanic_id' => $mekanik->id,
        'item_type' => 'service',
        'description' => 'Ganti Kampas Rem',
        'quantity' => 1,
        'unit_price' => 45000,
        'line_total' => 45000,
        'assigned_at' => Carbon::now(),
    ]);

    $cashierResponse = $this->actingAs($user)->getJson("/reports/staff-performance/cashier/{$kasir->id}");
    $cashierResponse->assertStatus(200);
    $cashierResponse->assertJsonStructure(['cashier', 'transactions']);
    expect($cashierResponse->json('transactions'))->toHaveCount(1);
    expect($cashierResponse->json('transactions.0.number'))->toBe('TRX-101');

    $mechanicResponse = $this->actingAs($user)->getJson("/reports/staff-performance/mechanic/{$mekanik->id}");
    $mechanicResponse->assertStatus(200);
    $mechanicResponse->assertJsonStructure(['mechanic', 'items']);
    expect($mechanicResponse->json('items'))->toHaveCount(1);
    expect($mechanicResponse->json('items.0.description'))->toBe('Ganti Kampas Rem');
});
