<?php

namespace Database\Seeders;

use App\Models\DocumentSequence;
use App\Models\Store;
use Illuminate\Database\Seeder;

class DocumentSequenceSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            'transaction' => ['prefix' => 'TRX', 'format' => '{STORE_CODE}/{PREFIX}/{YYYY}{MM}/{SEQ:4}'],
            'service_order' => ['prefix' => 'WO', 'format' => '{STORE_CODE}/{PREFIX}/{YYYY}{MM}/{SEQ:4}'],
            'purchase' => ['prefix' => 'PO', 'format' => '{STORE_CODE}/{PREFIX}/{YYYY}{MM}/{SEQ:4}'],
            'stock_adjustment' => ['prefix' => 'SA', 'format' => '{STORE_CODE}/{PREFIX}/{YYYY}{MM}/{SEQ:4}'],
            'stock_transfer' => ['prefix' => 'ST', 'format' => '{STORE_CODE}/{PREFIX}/{YYYY}{MM}/{SEQ:4}'],
        ];

        // Seed global defaults (store_id = null)
        foreach ($types as $type => $config) {
            DocumentSequence::updateOrCreate(
                ['type' => $type, 'store_id' => null],
                [
                    'prefix' => $config['prefix'],
                    'format_pattern' => $config['format'],
                    'reset_frequency' => 'monthly',
                    'padding' => 4,
                ]
            );
        }

        // Seed store-specific defaults
        $stores = Store::all();
        foreach ($stores as $store) {
            foreach ($types as $type => $config) {
                DocumentSequence::updateOrCreate(
                    ['type' => $type, 'store_id' => $store->id],
                    [
                        'prefix' => $config['prefix'],
                        'format_pattern' => $config['format'],
                        'reset_frequency' => 'monthly',
                        'padding' => 4,
                    ]
                );
            }
        }
    }
}
