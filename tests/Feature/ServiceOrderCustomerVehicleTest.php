<?php

use App\Models\CustomerVehicle;
use App\Models\Partner;
use App\Models\ServiceOrder;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('service order creates customer_vehicle automatically when customer_id and plate_number provided', function () {
    $user = User::factory()->create();
    $store = Store::create(['name' => 'Bengkel Utama', 'code' => 'BKL-01']);
    $customer = Partner::create(['name' => 'Budi Santoso', 'code' => 'CUST-001', 'kind' => 'person', 'phone' => '08123456789']);

    $this->actingAs($user)
        ->post(route('services.store'), [
            'store_id' => $store->id,
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'customer_phone' => $customer->phone,
            'plate_number' => 'B 9999 XYZ',
            'vehicle_brand' => 'Yamaha',
            'vehicle_model' => 'NMAX 155',
            'year' => 2023,
            'color' => 'Hitam',
            'status' => 'checkin',
            'general_complaint' => 'Ganti Oli Mesin',
            'items' => [
                [
                    'item_type' => 'labor',
                    'description' => 'Jasa Ganti Oli',
                    'quantity' => 1,
                    'unit_price' => 25000,
                ],
            ],
        ])
        ->assertRedirect(route('services.index'));

    expect(ServiceOrder::count())->toBe(1);
    $so = ServiceOrder::first();

    expect($so->customer_id)->toBe($customer->id)
        ->and($so->plate_number)->toBe('B 9999 XYZ')
        ->and($so->vehicle_id)->not->toBeNull();

    // Verify CustomerVehicle was created automatically
    $vehicle = CustomerVehicle::where('id', $so->vehicle_id)->first();
    expect($vehicle)->not->toBeNull()
        ->and($vehicle->customer_id)->toBe($customer->id)
        ->and($vehicle->plate_number)->toBe('B 9999 XYZ')
        ->and($vehicle->brand)->toBe('Yamaha')
        ->and($vehicle->model)->toBe('NMAX 155');
});
