<?php

namespace App\Services;

use App\Models\CashFlow;
use App\Models\CashFlowCategory;
use App\Models\InventoryBatch;
use App\Models\InventoryMovement;
use App\Models\Partner;
use App\Models\ProductStock;
use App\Models\Purchase;
use App\Models\Store;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PurchaseService
{
    public function __construct(
        protected DocumentSequenceService $documentSequenceService
    ) {}

    public function create(array $data, int $userId): Purchase
    {
        return DB::transaction(function () use ($data, $userId) {
            $purchaseDate = Carbon::parse($data['purchase_date']);
            $storeId = $data['store_id'] ?? null;
            $number = $this->generateNumber($purchaseDate, $storeId);

            // 1. Calculate item subtotal and totals
            $rawItemsSubtotal = 0;
            $itemsNetTotal = 0;

            foreach ($data['items'] as $itemData) {
                $qty = (int) $itemData['quantity_ordered'];
                $unitPrice = (float) $itemData['unit_purchase_price'];
                $itemSubtotal = $qty * $unitPrice;
                $rawItemsSubtotal += $itemSubtotal;

                $itemDiscount = 0;
                if (! empty($itemData['item_discount_type']) && isset($itemData['item_discount_value'])) {
                    if ($itemData['item_discount_type'] === 'percent') {
                        $itemDiscount = $itemSubtotal * ((float) $itemData['item_discount_value'] / 100);
                    } elseif ($itemData['item_discount_type'] === 'amount') {
                        $itemDiscount = (float) $itemData['item_discount_value'];
                    }
                }

                $itemsNetTotal += max(0, $itemSubtotal - $itemDiscount);
            }

            // 2. Calculate header discount
            $headerDiscountAmount = 0;
            if (! empty($data['discount_type']) && isset($data['discount_value'])) {
                if ($data['discount_type'] === 'percent') {
                    $headerDiscountAmount = $itemsNetTotal * ((float) $data['discount_value'] / 100);
                } elseif ($data['discount_type'] === 'amount') {
                    $headerDiscountAmount = (float) $data['discount_value'];
                }
            }

            $finalPrice = max(0, $itemsNetTotal - $headerDiscountAmount);

            // 3. Create Purchase Header
            $purchase = Purchase::create([
                'store_id' => $data['store_id'],
                'supplier_id' => $data['supplier_id'],
                'created_by' => $userId,
                'received_by' => $userId,
                'number' => $number,
                'invoice_number' => $data['invoice_number'] ?? null,
                'purchase_date' => $data['purchase_date'],
                'discount_type' => $data['discount_type'] ?? null,
                'discount_value' => $data['discount_value'] ?? 0,
                'price' => $finalPrice,
                'notes' => $data['notes'] ?? null,
            ]);

            // 4. Resolve Warehouse for the store
            $warehouse = Warehouse::where('store_id', $data['store_id'])->first();
            if (! $warehouse) {
                $store = Store::find($data['store_id']);
                $warehouse = Warehouse::create([
                    'store_id' => $data['store_id'],
                    'code' => 'WH-'.($store?->code ?? 'DEFAULT'),
                    'name' => 'Gudang Utama '.($store?->name ?? 'Cabang'),
                    'is_active' => true,
                ]);
            }

            // 5. Create Purchase Items, Inventory Batches & Update Stocks
            foreach ($data['items'] as $itemData) {
                $qty = (int) $itemData['quantity_ordered'];
                $unitPrice = (float) $itemData['unit_purchase_price'];
                $itemSubtotal = $qty * $unitPrice;

                $itemDiscount = 0;
                if (! empty($itemData['item_discount_type']) && isset($itemData['item_discount_value'])) {
                    if ($itemData['item_discount_type'] === 'percent') {
                        $itemDiscount = $itemSubtotal * ((float) $itemData['item_discount_value'] / 100);
                    } elseif ($itemData['item_discount_type'] === 'amount') {
                        $itemDiscount = (float) $itemData['item_discount_value'];
                    }
                }

                $itemNet = max(0, $itemSubtotal - $itemDiscount);

                // Pro-rate header discount per item
                $proRatedHeaderDiscount = $itemsNetTotal > 0 ? ($itemNet / $itemsNetTotal) * $headerDiscountAmount : 0;
                $finalItemCostTotal = max(0, $itemNet - $proRatedHeaderDiscount);
                $unitCost = $qty > 0 ? $finalItemCostTotal / $qty : 0;

                // Purchase Item
                $purchaseItem = $purchase->items()->create([
                    'product_variant_id' => $itemData['product_variant_id'],
                    'price_type' => $itemData['price_type'],
                    'quantity_ordered' => $qty,
                    'unit_purchase_price' => $unitPrice,
                    'item_discount_type' => $itemData['item_discount_type'] ?? null,
                    'item_discount_value' => $itemData['item_discount_value'] ?? 0,
                ]);

                // Inventory Batch (FIFO)
                $batch = InventoryBatch::create([
                    'product_variant_id' => $itemData['product_variant_id'],
                    'warehouse_id' => $warehouse->id,
                    'warehouse_location_id' => null,
                    'purchase_item_id' => $purchaseItem->id,
                    'initial_quantity' => $qty,
                    'current_quantity' => $qty,
                    'unit_cost' => $unitCost,
                    'received_at' => $data['purchase_date'],
                ]);

                // Product Stock
                $stock = ProductStock::where('product_variant_id', $itemData['product_variant_id'])
                    ->where('warehouse_id', $warehouse->id)
                    ->whereNull('warehouse_location_id')
                    ->first();

                if (! $stock) {
                    $stock = ProductStock::create([
                        'product_variant_id' => $itemData['product_variant_id'],
                        'warehouse_id' => $warehouse->id,
                        'warehouse_location_id' => null,
                        'quantity' => $qty,
                        'minimum_stock' => 0,
                        'is_hidden' => false,
                    ]);
                } else {
                    $stock->increment('quantity', $qty);
                }

                $totalStockBalance = (int) ProductStock::where('product_variant_id', $itemData['product_variant_id'])
                    ->where('warehouse_id', $warehouse->id)
                    ->sum('quantity');

                // Inventory Movement (Ledger)
                InventoryMovement::create([
                    'warehouse_id' => $warehouse->id,
                    'product_variant_id' => $itemData['product_variant_id'],
                    'inventory_batch_id' => $batch->id,
                    'reference_type' => Purchase::class,
                    'reference_id' => $purchase->id,
                    'type' => 'in',
                    'quantity' => $qty,
                    'balance_after' => $totalStockBalance,
                ]);
            }

            // 6. Record Cash Flow (Expense)
            $cashCategory = CashFlowCategory::where('name', 'Pembelian Sparepart & Stok')->first()
                ?? CashFlowCategory::where('type', 'expense')->first();

            $supplier = Partner::find($data['supplier_id']);
            $supplierName = $supplier?->name ?? 'Supplier';

            if ($cashCategory) {
                CashFlow::create([
                    'store_id' => $data['store_id'],
                    'user_id' => $userId,
                    'category_id' => $cashCategory->id,
                    'amount' => $finalPrice,
                    'date' => $data['purchase_date'],
                    'type' => 'expense',
                    'description' => "Pembelian Stok #{$number} dari supplier {$supplierName}",
                    'reference_type' => Purchase::class,
                    'reference_id' => $purchase->id,
                ]);
            }

            return $purchase;
        });
    }

    public function delete(Purchase $purchase): void
    {
        DB::transaction(function () use ($purchase) {
            // Delete related CashFlow
            CashFlow::where('reference_type', Purchase::class)
                ->where('reference_id', $purchase->id)
                ->delete();

            // Reverse Inventory Stock & Batches
            foreach ($purchase->items as $item) {
                $batches = InventoryBatch::where('purchase_item_id', $item->id)->get();
                foreach ($batches as $batch) {
                    $stock = ProductStock::where('product_variant_id', $item->product_variant_id)
                        ->where('warehouse_id', $batch->warehouse_id)
                        ->first();

                    if ($stock) {
                        $stock->decrement('quantity', min($stock->quantity, $batch->current_quantity));
                    }

                    $totalStockBalance = (int) ProductStock::where('product_variant_id', $item->product_variant_id)
                        ->where('warehouse_id', $batch->warehouse_id)
                        ->sum('quantity');

                    InventoryMovement::create([
                        'warehouse_id' => $batch->warehouse_id,
                        'product_variant_id' => $item->product_variant_id,
                        'inventory_batch_id' => $batch->id,
                        'reference_type' => Purchase::class,
                        'reference_id' => $purchase->id,
                        'type' => 'out',
                        'quantity' => $batch->current_quantity,
                        'balance_after' => $totalStockBalance,
                    ]);

                    $batch->delete();
                }

                $item->delete();
            }

            $purchase->delete();
        });
    }

    private function generateNumber(Carbon $date, ?string $storeId = null): string
    {
        return $this->documentSequenceService->generate('purchase', $storeId, $date);
    }
}
