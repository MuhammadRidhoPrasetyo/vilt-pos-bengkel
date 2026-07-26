<?php

namespace App\Services;

use App\Models\InventoryMovement;
use App\Models\ProductStock;
use Illuminate\Support\Facades\DB;

class ProductStockService
{
    public function setStock(array $data, ?string $stockId = null): ProductStock
    {
        return DB::transaction(function () use ($data, $stockId) {
            $locationId = ! empty($data['warehouse_location_id']) ? $data['warehouse_location_id'] : null;

            $stock = null;
            if ($stockId) {
                $stock = ProductStock::find($stockId);
            }

            if (! $stock) {
                $stock = ProductStock::where('product_variant_id', $data['product_variant_id'])
                    ->where('warehouse_id', $data['warehouse_id'])
                    ->where('warehouse_location_id', $locationId)
                    ->first();
            }

            $isNew = ! $stock;
            $previousQuantity = $stock ? $stock->quantity : 0;
            $newQuantity = (int) $data['quantity'];
            $delta = $newQuantity - $previousQuantity;

            if ($isNew) {
                $stock = ProductStock::create([
                    'product_variant_id' => $data['product_variant_id'],
                    'warehouse_id' => $data['warehouse_id'],
                    'warehouse_location_id' => $locationId,
                    'quantity' => $newQuantity,
                    'minimum_stock' => $data['minimum_stock'] ?? 0,
                    'is_hidden' => $data['is_hidden'] ?? false,
                ]);
                $referenceType = 'initial_stock';
            } else {
                $stock->update([
                    'product_variant_id' => $data['product_variant_id'],
                    'warehouse_id' => $data['warehouse_id'],
                    'warehouse_location_id' => $locationId,
                    'quantity' => $newQuantity,
                    'minimum_stock' => $data['minimum_stock'] ?? $stock->minimum_stock,
                    'is_hidden' => $data['is_hidden'] ?? $stock->is_hidden,
                ]);
                $referenceType = 'stock_adjustment';
            }

            if ($delta !== 0) {
                $totalBalanceAfter = (int) ProductStock::where('product_variant_id', $data['product_variant_id'])
                    ->where('warehouse_id', $data['warehouse_id'])
                    ->sum('quantity');

                InventoryMovement::create([
                    'warehouse_id' => $data['warehouse_id'],
                    'product_variant_id' => $data['product_variant_id'],
                    'reference_type' => $referenceType,
                    'reference_id' => $stock->id,
                    'type' => $delta > 0 ? 'in' : 'out',
                    'quantity' => abs($delta),
                    'balance_after' => $totalBalanceAfter,
                ]);
            }

            return $stock;
        });
    }

    public function deleteStock(ProductStock $stock): void
    {
        DB::transaction(function () use ($stock) {
            $previousQuantity = $stock->quantity;
            $variantId = $stock->product_variant_id;
            $warehouseId = $stock->warehouse_id;
            $stockId = $stock->id;

            $stock->delete();

            if ($previousQuantity > 0) {
                $totalBalanceAfter = (int) ProductStock::where('product_variant_id', $variantId)
                    ->where('warehouse_id', $warehouseId)
                    ->sum('quantity');

                InventoryMovement::create([
                    'warehouse_id' => $warehouseId,
                    'product_variant_id' => $variantId,
                    'reference_type' => 'stock_removal',
                    'reference_id' => $stockId,
                    'type' => 'out',
                    'quantity' => $previousQuantity,
                    'balance_after' => $totalBalanceAfter,
                ]);
            }
        });
    }
}
