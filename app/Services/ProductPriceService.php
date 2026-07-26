<?php

namespace App\Services;

use App\Models\ProductPrice;
use App\Models\ProductPriceHistory;
use Illuminate\Support\Facades\DB;

class ProductPriceService
{
    public function setPrice(array $data, ?string $priceId = null): ProductPrice
    {
        return DB::transaction(function () use ($data, $priceId) {
            $storeId = ! empty($data['store_id']) ? $data['store_id'] : null;

            $price = null;
            if ($priceId) {
                $price = ProductPrice::find($priceId);
            }

            if (! $price) {
                $price = ProductPrice::where('product_variant_id', $data['product_variant_id'])
                    ->where('store_id', $storeId)
                    ->first();
            }

            $purchasePrice = (float) $data['purchase_price'];
            $sellingPrice = (float) $data['selling_price'];
            $markup = isset($data['markup']) ? (float) $data['markup'] : 0;
            $markupType = $data['markup_type'] ?? null;
            $isActive = isset($data['is_active']) ? (bool) $data['is_active'] : true;

            if (! $price) {
                $price = ProductPrice::create([
                    'product_variant_id' => $data['product_variant_id'],
                    'store_id' => $storeId,
                    'price_type' => 'toko',
                    'purchase_price' => $purchasePrice,
                    'markup' => $markup,
                    'markup_type' => $markupType,
                    'selling_price' => $sellingPrice,
                    'is_active' => $isActive,
                ]);
            } else {
                $price->update([
                    'product_variant_id' => $data['product_variant_id'],
                    'store_id' => $storeId,
                    'price_type' => 'toko',
                    'purchase_price' => $purchasePrice,
                    'markup' => $markup,
                    'markup_type' => $markupType,
                    'selling_price' => $sellingPrice,
                    'is_active' => $isActive,
                ]);
            }

            ProductPriceHistory::create([
                'product_variant_id' => $data['product_variant_id'],
                'store_id' => $storeId,
                'product_price_id' => $price->id,
                'purchase_price' => $purchasePrice,
                'selling_price' => $sellingPrice,
                'date' => now(),
            ]);

            return $price;
        });
    }

    public function deletePrice(ProductPrice $price): void
    {
        $price->delete();
    }
}
