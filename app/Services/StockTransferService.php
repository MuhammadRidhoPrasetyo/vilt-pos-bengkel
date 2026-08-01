<?php

namespace App\Services;

use App\Models\InventoryBatch;
use App\Models\InventoryMovement;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Models\StockTransfer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockTransferService
{
    public function create(array $data, string $userId): StockTransfer
    {
        return DB::transaction(function () use ($data, $userId) {
            $this->ensureDifferentStores($data['from_store_id'], $data['to_store_id']);

            $transfer = StockTransfer::create([
                'from_store_id' => $data['from_store_id'],
                'to_store_id' => $data['to_store_id'],
                'status' => 'draft',
                'reference_number' => $data['reference_number'] ?? $this->generateNumber(),
                'occurred_at' => $data['occurred_at'] ?? now(),
                'created_by' => $userId,
                'posted_by' => null,
                'posted_at' => null,
                'note' => $data['note'] ?? null,
            ]);

            $this->syncItems($transfer, $data['items']);

            return $transfer->fresh(['fromStore', 'toStore', 'items.productVariant.product', 'items.fromWarehouse', 'items.toWarehouse']);
        });
    }

    public function update(StockTransfer $transfer, array $data): StockTransfer
    {
        $this->ensureDraft($transfer);

        return DB::transaction(function () use ($transfer, $data) {
            $this->ensureDifferentStores($data['from_store_id'], $data['to_store_id']);

            $transfer->update([
                'from_store_id' => $data['from_store_id'],
                'to_store_id' => $data['to_store_id'],
                'reference_number' => $data['reference_number'] ?? $transfer->reference_number,
                'occurred_at' => $data['occurred_at'] ?? $transfer->occurred_at,
                'note' => $data['note'] ?? null,
            ]);

            $transfer->items()->delete();
            $this->syncItems($transfer, $data['items']);

            return $transfer->fresh(['fromStore', 'toStore', 'items.productVariant.product', 'items.fromWarehouse', 'items.toWarehouse']);
        });
    }

    public function post(StockTransfer $transfer, string $userId): StockTransfer
    {
        $this->ensureDraft($transfer);

        return DB::transaction(function () use ($transfer, $userId) {
            $transfer->load('items.productVariant');

            foreach ($transfer->items as $item) {
                $this->postItem($transfer, $item);
            }

            $transfer->update([
                'status' => 'posted',
                'posted_by' => $userId,
                'posted_at' => now(),
            ]);

            return $transfer->fresh(['fromStore', 'toStore', 'createdBy', 'postedBy', 'items.productVariant.product', 'items.fromWarehouse', 'items.toWarehouse']);
        });
    }

    public function cancel(StockTransfer $transfer): StockTransfer
    {
        $this->ensureDraft($transfer);
        $transfer->update(['status' => 'cancelled']);

        return $transfer->refresh();
    }

    public function delete(StockTransfer $transfer): void
    {
        $this->ensureDraft($transfer);
        $transfer->delete();
    }

    private function syncItems(StockTransfer $transfer, array $items): void
    {
        foreach ($items as $item) {
            $variant = ProductVariant::findOrFail($item['product_variant_id']);

            $transfer->items()->create([
                'product_variant_id' => $variant->id,
                'from_warehouse_id' => $item['from_warehouse_id'],
                'from_warehouse_location_id' => $item['from_warehouse_location_id'] ?? null,
                'to_warehouse_id' => $item['to_warehouse_id'],
                'to_warehouse_location_id' => $item['to_warehouse_location_id'] ?? null,
                'quantity' => $item['quantity'],
                'unit_cost' => $item['unit_cost'] ?? $variant->default_purchase_price ?? 0,
                'product_price_id' => $item['product_price_id'] ?? null,
            ]);
        }
    }

    private function postItem(StockTransfer $transfer, $item): void
    {
        if ($item->from_warehouse_id === $item->to_warehouse_id && $item->from_warehouse_location_id === $item->to_warehouse_location_id) {
            throw ValidationException::withMessages([
                'items' => 'Gudang/lokasi asal dan tujuan tidak boleh sama.',
            ]);
        }

        $sourceStock = $this->findStock($item->product_variant_id, $item->from_warehouse_id, $item->from_warehouse_location_id);
        if (! $sourceStock || $sourceStock->quantity < $item->quantity) {
            throw ValidationException::withMessages([
                'items' => "Stok {$item->productVariant?->display_receipt_name} tidak cukup untuk transfer.",
            ]);
        }

        $destinationStock = $this->firstOrCreateStock($item->product_variant_id, $item->to_warehouse_id, $item->to_warehouse_location_id);
        $sourceStock->decrement('quantity', $item->quantity);
        $destinationStock->increment('quantity', $item->quantity);

        $remaining = (int) $item->quantity;

        foreach ($this->availableBatches($item->product_variant_id, $item->from_warehouse_id, $item->from_warehouse_location_id) as $batch) {
            if ($remaining <= 0) {
                break;
            }

            $quantity = min($remaining, (int) $batch->current_quantity);
            $unitCost = (float) $batch->unit_cost;
            $batch->decrement('current_quantity', $quantity);
            $remaining -= $quantity;

            $destinationBatch = InventoryBatch::create([
                'product_variant_id' => $item->product_variant_id,
                'warehouse_id' => $item->to_warehouse_id,
                'warehouse_location_id' => $item->to_warehouse_location_id,
                'purchase_item_id' => null,
                'initial_quantity' => $quantity,
                'current_quantity' => $quantity,
                'unit_cost' => $unitCost,
                'received_at' => $transfer->occurred_at ?? now(),
            ]);

            $this->recordMovement($item->from_warehouse_id, $item->product_variant_id, $batch->id, StockTransfer::class, $transfer->id, 'out', $quantity);
            $this->recordMovement($item->to_warehouse_id, $item->product_variant_id, $destinationBatch->id, StockTransfer::class, $transfer->id, 'in', $quantity);
        }

        if ($remaining > 0) {
            $destinationBatch = InventoryBatch::create([
                'product_variant_id' => $item->product_variant_id,
                'warehouse_id' => $item->to_warehouse_id,
                'warehouse_location_id' => $item->to_warehouse_location_id,
                'purchase_item_id' => null,
                'initial_quantity' => $remaining,
                'current_quantity' => $remaining,
                'unit_cost' => $item->unit_cost,
                'received_at' => $transfer->occurred_at ?? now(),
            ]);

            $this->recordMovement($item->from_warehouse_id, $item->product_variant_id, null, StockTransfer::class, $transfer->id, 'out', $remaining);
            $this->recordMovement($item->to_warehouse_id, $item->product_variant_id, $destinationBatch->id, StockTransfer::class, $transfer->id, 'in', $remaining);
        }
    }

    private function firstOrCreateStock(string $variantId, string $warehouseId, ?string $locationId): ProductStock
    {
        return ProductStock::firstOrCreate([
            'product_variant_id' => $variantId,
            'warehouse_id' => $warehouseId,
            'warehouse_location_id' => $locationId,
        ], [
            'quantity' => 0,
            'minimum_stock' => 0,
            'is_hidden' => false,
        ]);
    }

    private function findStock(string $variantId, string $warehouseId, ?string $locationId): ?ProductStock
    {
        return ProductStock::where('product_variant_id', $variantId)
            ->where('warehouse_id', $warehouseId)
            ->where('warehouse_location_id', $locationId)
            ->lockForUpdate()
            ->first();
    }

    private function availableBatches(string $variantId, string $warehouseId, ?string $locationId): Collection
    {
        return InventoryBatch::where('product_variant_id', $variantId)
            ->where('warehouse_id', $warehouseId)
            ->where('warehouse_location_id', $locationId)
            ->where('current_quantity', '>', 0)
            ->orderBy('received_at')
            ->orderBy('created_at')
            ->lockForUpdate()
            ->get();
    }

    private function recordMovement(string $warehouseId, string $variantId, ?string $batchId, string $referenceType, string $referenceId, string $type, int $quantity): void
    {
        $balanceAfter = (int) ProductStock::where('product_variant_id', $variantId)
            ->where('warehouse_id', $warehouseId)
            ->sum('quantity');

        InventoryMovement::create([
            'warehouse_id' => $warehouseId,
            'product_variant_id' => $variantId,
            'inventory_batch_id' => $batchId,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'type' => $type,
            'quantity' => $quantity,
            'balance_after' => $balanceAfter,
        ]);
    }

    private function ensureDraft(StockTransfer $transfer): void
    {
        if ($transfer->status !== 'draft') {
            throw ValidationException::withMessages([
                'status' => 'Hanya stock transfer draft yang dapat diproses.',
            ]);
        }
    }

    private function ensureDifferentStores(string $fromStoreId, string $toStoreId): void
    {
        if ($fromStoreId === $toStoreId) {
            throw ValidationException::withMessages([
                'to_store_id' => 'Toko asal dan tujuan tidak boleh sama.',
            ]);
        }
    }

    private function generateNumber(): string
    {
        return 'TRF-'.now()->format('Ymd-His');
    }
}
