<?php

use App\Models\DocumentSequence;
use App\Models\Store;
use App\Services\DocumentSequenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('document sequence service generates sequential formatted numbers', function () {
    $store = Store::create([
        'code' => 'BKL-01',
        'name' => 'Bengkel Maju Jaya',
    ]);

    DocumentSequence::create([
        'type' => 'transaction',
        'store_id' => $store->id,
        'prefix' => 'TRX',
        'format_pattern' => '{STORE_CODE}/{PREFIX}/{YYYY}{MM}/{SEQ:4}',
        'reset_frequency' => 'monthly',
        'sequence' => 0,
        'year' => (int) now()->format('Y'),
        'month' => (int) now()->format('m'),
        'padding' => 4,
    ]);

    $service = app(DocumentSequenceService::class);

    $number1 = $service->generate('transaction', $store->id);
    $number2 = $service->generate('transaction', $store->id);

    $expectedMonth = now()->format('Ym');
    expect($number1)->toBe("BKL-01/TRX/{$expectedMonth}/0001");
    expect($number2)->toBe("BKL-01/TRX/{$expectedMonth}/0002");
});

test('document sequence service supports store name token', function () {
    $store = Store::create([
        'code' => 'BKL-02',
        'name' => 'Bengkel Central Utama',
    ]);

    DocumentSequence::create([
        'type' => 'service_order',
        'store_id' => $store->id,
        'prefix' => 'WO',
        'format_pattern' => '{STORE_NAME}-{PREFIX}-{YYYY}-{SEQ:4}',
        'reset_frequency' => 'yearly',
        'sequence' => 0,
        'year' => (int) now()->format('Y'),
        'padding' => 4,
    ]);

    $service = app(DocumentSequenceService::class);
    $number = $service->generate('service_order', $store->id);

    $year = now()->format('Y');
    expect($number)->toBe("BENGKEL-CENTRAL-UTAMA-WO-{$year}-0001");
});

test('document sequence service handles fallback when sequence record does not exist', function () {
    $store = Store::create([
        'code' => 'STR-99',
        'name' => 'Toko Baru',
    ]);

    $service = app(DocumentSequenceService::class);
    $number = $service->generate('purchase', $store->id);

    $month = now()->format('Ym');
    expect($number)->toBe("STR-99/PO/{$month}/0001");
});
