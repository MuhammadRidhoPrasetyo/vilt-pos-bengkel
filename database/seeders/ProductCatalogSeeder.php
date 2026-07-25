<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use App\Models\Unit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductCatalogSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $brand = Brand::query()->firstOrCreate([
                'name' => 'Yamaha',
            ]);

            $unit = Unit::query()->firstOrCreate([
                'name' => 'Pcs',
            ], [
                'symbol' => 'pcs',
            ]);

            $sparepartCategory = ProductCategory::query()->firstOrCreate([
                'name' => 'Sparepart',
                'parent_id' => null,
            ], [
                'pricing_mode' => 'fixed',
            ]);

            $oilCategory = ProductCategory::query()->firstOrCreate([
                'name' => 'Oli Mesin',
                'parent_id' => $sparepartCategory->id,
            ], [
                'pricing_mode' => 'fixed',
            ]);

            $colorAttribute = Attribute::query()->firstOrCreate([
                'name' => 'Warna',
            ]);

            $sizeAttribute = Attribute::query()->firstOrCreate([
                'name' => 'Ukuran',
            ]);

            $typeAttribute = Attribute::query()->firstOrCreate([
                'name' => 'Tipe',
            ]);

            $colorOptions = collect(['Merah', 'Biru', 'Hitam'])->mapWithKeys(
                fn (string $value) => [
                    $value => $colorAttribute->options()->firstOrCreate(['value' => $value]),
                ]
            );

            $sizeOptions = collect(['S', 'M', 'L'])->mapWithKeys(
                fn (string $value) => [
                    $value => $sizeAttribute->options()->firstOrCreate(['value' => $value]),
                ]
            );

            $typeOptions = collect(['Manual', 'Matic'])->mapWithKeys(
                fn (string $value) => [
                    $value => $typeAttribute->options()->firstOrCreate(['value' => $value]),
                ]
            );

            $product = Product::query()->firstOrCreate([
                'name' => 'Oli Yamalube',
                'product_category_id' => $oilCategory->id,
            ], [
                'brand_id' => $brand->id,
                'unit_id' => $unit->id,
                'item_type' => 'part',
                'has_variants' => true,
                'description' => 'Produk contoh untuk uji product variant.',
            ]);

            $variants = [
                [
                    'sku' => 'YML-MR-S-MNL',
                    'barcode' => '899100000001',
                    'name_suffix' => 'Merah / S / Manual',
                    'default_purchase_price' => 45000,
                    'default_selling_price' => 55000,
                    'is_active' => true,
                    'options' => [
                        $colorOptions['Merah']->id,
                        $sizeOptions['S']->id,
                        $typeOptions['Manual']->id,
                    ],
                ],
                [
                    'sku' => 'YML-BR-M-MTC',
                    'barcode' => '899100000002',
                    'name_suffix' => 'Biru / M / Matic',
                    'default_purchase_price' => 46000,
                    'default_selling_price' => 57000,
                    'is_active' => true,
                    'options' => [
                        $colorOptions['Biru']->id,
                        $sizeOptions['M']->id,
                        $typeOptions['Matic']->id,
                    ],
                ],
                [
                    'sku' => 'YML-HT-L-MNL',
                    'barcode' => '899100000003',
                    'name_suffix' => 'Hitam / L / Manual',
                    'default_purchase_price' => 47000,
                    'default_selling_price' => 58000,
                    'is_active' => true,
                    'options' => [
                        $colorOptions['Hitam']->id,
                        $sizeOptions['L']->id,
                        $typeOptions['Manual']->id,
                    ],
                ],
            ];

            foreach ($variants as $variantData) {
                $attributeOptionIds = $variantData['options'];
                unset($variantData['options']);

                $variant = ProductVariant::query()->updateOrCreate([
                    'product_id' => $product->id,
                    'sku' => $variantData['sku'],
                ], $variantData);

                $variant->attributeOptions()->sync($attributeOptionIds);
            }
        });
    }
}
