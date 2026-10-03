<?php

namespace App\Services;

use App\Models\InventoryBatch;
use App\Models\InventoryMovement;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Models\StockOpname;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockOpnameService
{
    public function __construct(
        protected DocumentSequenceService $sequenceService
    ) {}

    public function create(array $data, string $userId): StockOpname
    {
        return DB::transaction(function () use ($data, $userId) {
            $occurredAt = isset($data['occurred_at']) && ! empty($data['occurred_at']) ? $data['occurred_at'] : now();
            $opnameNumber = $data['opname_number'] ?? $this->sequenceService->generate('stock_opname', $data['store_id']);

            $opname = StockOpname::create([
                'store_id' => $data['store_id'],
                'warehouse_id' => $data['warehouse_id'],
                'opname_number' => $opnameNumber,
                'status' => 'draft',
                'occurred_at' => $occurredAt,
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
            ]);

            $this->syncItems($opname, $data['items']);

            return $opname->fresh(['store', 'warehouse', 'createdBy', 'items.productVariant.product', 'items.warehouseLocation']);
        });
    }

    public function update(StockOpname $opname, array $data): StockOpname
    {
        $this->ensureDraft($opname);

        return DB::transaction(function () use ($opname, $data) {
            $opname->update([
                'opname_number' => $data['opname_number'] ?? $opname->opname_number,
                'occurred_at' => $data['occurred_at'] ?? $opname->occurred_at,
                'notes' => $data['notes'] ?? $opname->notes,
            ]);

            $opname->items()->delete();
            $this->syncItems($opname, $data['items']);

            return $opname->fresh(['store', 'warehouse', 'createdBy', 'items.productVariant.product', 'items.warehouseLocation']);
        });
    }

    public function post(StockOpname $opname, string $userId): StockOpname
    {
        $this->ensureDraft($opname);

        return DB::transaction(function () use ($opname, $userId) {
            $opname->load(['items.productVariant']);

            foreach ($opname->items as $item) {
                $difference = (int) $item->difference_quantity;
                if ($difference === 0) {
                    continue;
                }

                if ($difference > 0) {
                    $this->postIncrease($opname, $item, $difference);
                } else {
                    $this->postDecrease($opname, $item, abs($difference));
                }
            }

            $opname->update([
                'status' => 'posted',
                'posted_by' => $userId,
                'posted_at' => now(),
            ]);

            return $opname->fresh(['store', 'warehouse', 'createdBy', 'postedBy', 'items.productVariant.product', 'items.warehouseLocation']);
        });
    }

    public function cancel(StockOpname $opname): StockOpname
    {
        $this->ensureDraft($opname);
        $opname->update(['status' => 'cancelled']);

        return $opname->refresh();
    }

    public function delete(StockOpname $opname): void
    {
        $this->ensureDraft($opname);
        $opname->items()->delete();
        $opname->delete();
    }

    public function getWarehouseStockItems(string $warehouseId): Collection
    {
        return ProductStock::query()
            ->with(['productVariant.product', 'warehouseLocation'])
            ->where('warehouse_id', $warehouseId)
            ->get()
            ->map(function (ProductStock $stock) {
                return [
                    'product_variant_id' => $stock->product_variant_id,
                    'variant_name' => $stock->productVariant?->display_receipt_name ?? $stock->productVariant?->name,
                    'sku' => $stock->productVariant?->sku ?? '-',
                    'unit_name' => $stock->productVariant?->unit_name ?? 'Pcs',
                    'warehouse_location_id' => $stock->warehouse_location_id,
                    'location_name' => $stock->warehouseLocation?->name ?? 'Tanpa Rak/Lokasi',
                    'system_quantity' => (int) $stock->quantity,
                    'physical_quantity' => (int) $stock->quantity,
                    'difference_quantity' => 0,
                    'unit_cost' => (float) ($stock->productVariant?->default_purchase_price ?? 0),
                    'difference_value' => 0,
                    'note' => '',
                ];
            });
    }

    private function syncItems(StockOpname $opname, array $items): void
    {
        $totalSystemQty = 0;
        $totalPhysicalQty = 0;
        $totalDifferenceQty = 0;
        $totalDifferenceValue = 0;

        foreach ($items as $item) {
            $variant = ProductVariant::findOrFail($item['product_variant_id']);
            $systemQty = (int) ($item['system_quantity'] ?? 0);
            $physicalQty = (int) ($item['physical_quantity'] ?? 0);
            $diffQty = $physicalQty - $systemQty;
            $unitCost = (float) ($item['unit_cost'] ?? $variant->default_purchase_price ?? 0);
            $diffValue = $diffQty * $unitCost;

            $opname->items()->create([
                'product_variant_id' => $variant->id,
                'warehouse_location_id' => $item['warehouse_location_id'] ?? null,
                'system_quantity' => $systemQty,
                'physical_quantity' => $physicalQty,
                'difference_quantity' => $diffQty,
                'unit_cost' => $unitCost,
                'difference_value' => $diffValue,
                'note' => $item['note'] ?? null,
            ]);

            $totalSystemQty += $systemQty;
            $totalPhysicalQty += $physicalQty;
            $totalDifferenceQty += $diffQty;
            $totalDifferenceValue += $diffValue;
        }

        $opname->update([
            'total_system_qty' => $totalSystemQty,
            'total_physical_qty' => $totalPhysicalQty,
            'total_difference_qty' => $totalDifferenceQty,
            'total_difference_value' => $totalDifferenceValue,
        ]);
    }

    private function postIncrease(StockOpname $opname, $item, int $quantity): void
    {
        $stock = $this->firstOrCreateStock($item->product_variant_id, $opname->warehouse_id, $item->warehouse_location_id);
        $stock->increment('quantity', $quantity);

        $batch = InventoryBatch::create([
            'product_variant_id' => $item->product_variant_id,
            'warehouse_id' => $opname->warehouse_id,
            'warehouse_location_id' => $item->warehouse_location_id,
            'purchase_item_id' => null,
            'initial_quantity' => $quantity,
            'current_quantity' => $quantity,
            'unit_cost' => $item->unit_cost,
            'received_at' => $opname->occurred_at ?? now(),
        ]);

        $this->recordMovement($opname->warehouse_id, $item->product_variant_id, $batch->id, StockOpname::class, $opname->id, 'in', $quantity);
    }

    private function postDecrease(StockOpname $opname, $item, int $quantity): void
    {
        $stock = $this->findStock($item->product_variant_id, $opname->warehouse_id, $item->warehouse_location_id);
        if ($stock) {
            $deductQty = min($quantity, (int) $stock->quantity);
            if ($deductQty > 0) {
                $stock->decrement('quantity', $deductQty);
            }
        }

        $remaining = $quantity;

        foreach ($this->availableBatches($item->product_variant_id, $opname->warehouse_id, $item->warehouse_location_id) as $batch) {
            if ($remaining <= 0) {
                break;
            }

            $currentBatchQty = (int) $batch->current_quantity;
            $deduct = min($remaining, $currentBatchQty);
            $batch->decrement('current_quantity', $deduct);
            $remaining -= $deduct;

            $this->recordMovement($opname->warehouse_id, $item->product_variant_id, $batch->id, StockOpname::class, $opname->id, 'out', $deduct);
        }

        if ($remaining > 0) {
            $this->recordMovement($opname->warehouse_id, $item->product_variant_id, null, StockOpname::class, $opname->id, 'out', $remaining);
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
            ->when($locationId, fn ($q) => $q->where('warehouse_location_id', $locationId), fn ($q) => $q->whereNull('warehouse_location_id'))
            ->first();
    }

    private function availableBatches(string $variantId, string $warehouseId, ?string $locationId)
    {
        return InventoryBatch::where('product_variant_id', $variantId)
            ->where('warehouse_id', $warehouseId)
            ->when($locationId, fn ($q) => $q->where('warehouse_location_id', $locationId), fn ($q) => $q->whereNull('warehouse_location_id'))
            ->where('current_quantity', '>', 0)
            ->orderBy('received_at')
            ->orderBy('created_at')
            ->get();
    }

    private function recordMovement(string $warehouseId, string $variantId, ?string $batchId, string $type, string $id, string $direction, int $quantity): void
    {
        $balanceAfter = (int) ProductStock::where('product_variant_id', $variantId)
            ->where('warehouse_id', $warehouseId)
            ->sum('quantity');

        InventoryMovement::create([
            'warehouse_id' => $warehouseId,
            'product_variant_id' => $variantId,
            'inventory_batch_id' => $batchId,
            'reference_type' => $type,
            'reference_id' => $id,
            'type' => $direction,
            'quantity' => $quantity,
            'balance_after' => $balanceAfter,
        ]);
    }

    private function ensureDraft(StockOpname $opname): void
    {
        if ($opname->status !== 'draft') {
            throw ValidationException::withMessages([
                'status' => 'Hanya dokumen berstatus draft yang dapat diubah atau diposting.',
            ]);
        }
    }
}
