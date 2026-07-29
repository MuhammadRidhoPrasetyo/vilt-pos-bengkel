<?php

use App\Models\ServiceOrder;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('can view tv display and update service order status for kanban board', function () {
    $user = User::factory()->create();
    $store = Store::create(['name' => 'Bengkel Utama', 'code' => 'BKL-01']);

    $serviceOrder = ServiceOrder::create([
        'number' => 'SO-20260729-0001',
        'store_id' => $store->id,
        'customer_name' => 'Budi Santoso',
        'customer_phone' => '08123456789',
        'plate_number' => 'B 1234 ABC',
        'vehicle_brand' => 'Honda',
        'vehicle_model' => 'Vario 150',
        'status' => 'checkin',
        'checkin_at' => now(),
        'general_complaint' => 'Ganti oli dan cek rem',
        'estimated_total' => 150000,
    ]);

    // 1. Can view TV Display screen
    $response = $this->actingAs($user)
        ->get(route('services.display'))
        ->assertOk();

    // 2. Can update status to in_progress via PATCH endpoint
    $this->actingAs($user)
        ->patch(route('services.status.update', $serviceOrder->id), [
            'status' => 'in_progress',
            'mechanic_id' => $user->id,
        ])
        ->assertRedirect();

    $serviceOrder->refresh();
    expect($serviceOrder->status)->toBe('in_progress');

    // 3. Can update status to ready
    $this->actingAs($user)
        ->patch(route('services.status.update', $serviceOrder->id), [
            'status' => 'ready',
        ])
        ->assertRedirect();

    $serviceOrder->refresh();
    expect($serviceOrder->status)->toBe('ready')
        ->and($serviceOrder->completed_at)->not->toBeNull();
});
