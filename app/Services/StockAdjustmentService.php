<?php

namespace App\Services;

use App\Models\InventoryBatch;
use App\Models\InventoryMovement;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Models\StockAdjustment;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockAdjustmentService
{
    public function __construct(
        protected DocumentSequenceService $documentSequenceService
    ) {}

    public function create(array $data, string $userId): StockAdjustment
    {
        return DB::transaction(function () use ($data) {
            $occurredAt = isset($data['occurred_at']) ? Carbon::parse($data['occurred_at']) : now();
            $storeId = $data['store_id'] ?? null;

            $adjustment = StockAdjustment::create([
                'store_id' => $data['store_id'],
                'status' => 'draft',
                'posted_by' => null,
                'reference_number' => $data['reference_number'] ?? $this->generateNumber($storeId, $occurredAt),
                'occurred_at' => $occurredAt,
                'note' => $data['note'] ?? null,
            ]);

            $this->syncItems($adjustment, $data['items']);

            return $adjustment->fresh(['store', 'items.productVariant.product', 'items.warehouse', 'items.warehouseLocation']);
        });
    }

    public function update(StockAdjustment $adjustment, array $data): StockAdjustment
    {
        $this->ensureDraft($adjustment);

        return DB::transaction(function () use ($adjustment, $data) {
            $adjustment->update([
                'store_id' => $data['store_id'],
                'reference_number' => $data['reference_number'] ?? $adjustment->reference_number,
                'occurred_at' => $data['occurred_at'] ?? $adjustment->occurred_at,
                'note' => $data['note'] ?? null,
            ]);

            $adjustment->items()->delete();
            $this->syncItems($adjustment, $data['items']);

            return $adjustment->fresh(['store', 'items.productVariant.product', 'items.warehouse', 'items.warehouseLocation']);
        });
    }

    public function post(StockAdjustment $adjustment, string $userId): StockAdjustment
    {
        $this->ensureDraft($adjustment);

        return DB::transaction(function () use ($adjustment, $userId) {
            $adjustment->load(['items.productVariant']);

            foreach ($adjustment->items as $item) {
                if ($item->adjustment_type === 'increase') {
                    $this->postIncrease($adjustment, $item);

                    continue;
                }

                $this->postDecrease($adjustment, $item);
            }

            $adjustment->update([
                'status' => 'posted',
                'posted_by' => $userId,
            ]);

            return $adjustment->fresh(['store', 'postedBy', 'items.productVariant.product', 'items.warehouse', 'items.warehouseLocation']);
        });
    }

    public function cancel(StockAdjustment $adjustment): StockAdjustment
    {
        $this->ensureDraft($adjustment);
        $adjustment->update(['status' => 'cancelled']);

        return $adjustment->refresh();
    }

    public function delete(StockAdjustment $adjustment): void
    {
        $this->ensureDraft($adjustment);
        $adjustment->delete();
    }

    private function syncItems(StockAdjustment $adjustment, array $items): void
    {
        foreach ($items as $item) {
            $variant = ProductVariant::findOrFail($item['product_variant_id']);

            $adjustment->items()->create([
                'product_id' => $variant->product_id,
                'product_variant_id' => $variant->id,
                'warehouse_id' => $item['warehouse_id'],
                'warehouse_location_id' => $item['warehouse_location_id'] ?? null,
                'adjustment_type' => $item['adjustment_type'],
                'quantity' => $item['quantity'],
                'unit_cost' => $item['unit_cost'] ?? $variant->default_purchase_price ?? 0,
                'note' => $item['note'] ?? null,
            ]);
        }
    }

    private function postIncrease(StockAdjustment $adjustment, $item): void
    {
        $stock = $this->firstOrCreateStock($item->product_variant_id, $item->warehouse_id, $item->warehouse_location_id);
        $stock->increment('quantity', $item->quantity);

        $batch = InventoryBatch::create([
            'product_variant_id' => $item->product_variant_id,
            'warehouse_id' => $item->warehouse_id,
            'warehouse_location_id' => $item->warehouse_location_id,
            'purchase_item_id' => null,
            'initial_quantity' => $item->quantity,
            'current_quantity' => $item->quantity,
            'unit_cost' => $item->unit_cost,
            'received_at' => $adjustment->occurred_at ?? now(),
        ]);

        $this->recordMovement($item->warehouse_id, $item->product_variant_id, $batch->id, StockAdjustment::class, $adjustment->id, 'in', $item->quantity);
    }

    private function postDecrease(StockAdjustment $adjustment, $item): void
    {
        $stock = $this->findStock($item->product_variant_id, $item->warehouse_id, $item->warehouse_location_id);
        if (! $stock || $stock->quantity < $item->quantity) {
            throw ValidationException::withMessages([
                'items' => "Stok {$item->productVariant?->display_receipt_name} tidak cukup untuk adjustment decrease.",
            ]);
        }

        $stock->decrement('quantity', $item->quantity);
        $remaining = (int) $item->quantity;

        foreach ($this->availableBatches($item->product_variant_id, $item->warehouse_id, $item->warehouse_location_id) as $batch) {
            if ($remaining <= 0) {
                break;
            }

            $quantity = min($remaining, (int) $batch->current_quantity);
            $batch->decrement('current_quantity', $quantity);
            $remaining -= $quantity;
            $this->recordMovement($item->warehouse_id, $item->product_variant_id, $batch->id, StockAdjustment::class, $adjustment->id, 'out', $quantity);
        }

        if ($remaining > 0) {
            $this->recordMovement($item->warehouse_id, $item->product_variant_id, null, StockAdjustment::class, $adjustment->id, 'out', $remaining);
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

    private function ensureDraft(StockAdjustment $adjustment): void
    {
        if ($adjustment->status !== 'draft') {
            throw ValidationException::withMessages([
                'status' => 'Hanya stock adjustment draft yang dapat diproses.',
            ]);
        }
    }

    private function generateNumber(?string $storeId = null, ?Carbon $date = null): string
    {
        return $this->documentSequenceService->generate('stock_adjustment', $storeId, $date);
    }
}
