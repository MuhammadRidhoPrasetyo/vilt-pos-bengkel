<?php

namespace App\Services;

use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Models\ServiceOrder;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TransactionService
{
    public function create(array $data, string $userId): Transaction
    {
        return DB::transaction(function () use ($data, $userId) {
            $now = now();
            $number = $this->generateNumber($now);

            $storeId = $data['store_id'];
            $itemsData = $data['items'] ?? [];

            $subtotal = 0;
            $itemDiscountTotal = 0;
            $totalCost = 0;

            // Pre-calculate items
            $processedItems = [];
            foreach ($itemsData as $item) {
                $qty = (int) $item['quantity'];
                $unitPrice = (float) $item['unit_price'];
                $itemType = $item['item_type'] ?? 'part';
                $description = $item['description'] ?? null;
                $variantId = $item['product_variant_id'] ?? null;

                $itemDiscMode = $item['item_discount_mode'] ?? null;
                $itemDiscVal = (float) ($item['item_discount_value'] ?? 0);
                $itemDiscAmount = 0;

                if ($itemDiscMode === 'percent') {
                    $itemDiscAmount = ($unitPrice * ($itemDiscVal / 100)) * $qty;
                } elseif ($itemDiscMode === 'amount') {
                    $itemDiscAmount = $itemDiscVal * $qty;
                }

                $lineSubtotal = $unitPrice * $qty;
                $lineTotal = max(0, $lineSubtotal - $itemDiscAmount);
                $finalUnitPrice = $qty > 0 ? ($lineTotal / $qty) : $unitPrice;

                // Determine unit cost from ProductStock or variant
                $unitCost = 0;
                $stockId = null;

                if ($itemType === 'part' && ! empty($variantId)) {
                    $stock = ProductStock::where('product_variant_id', $variantId)
                        ->when($storeId, function ($sq) use ($storeId) {
                            $sq->whereHas('warehouse', fn ($wq) => $wq->where('store_id', $storeId));
                        })
                        ->first();

                    if (! $stock) {
                        $stock = ProductStock::where('product_variant_id', $variantId)->first();
                    }

                    if ($stock) {
                        $variantModel = ProductVariant::find($variantId);
                        $unitCost = (float) ($variantModel?->default_purchase_price ?? 0);
                        $stockId = $stock->id;
                    }
                }

                $lineCostTotal = $unitCost * $qty;
                $lineProfit = $lineTotal - $lineCostTotal;

                $subtotal += $lineSubtotal;
                $itemDiscountTotal += $itemDiscAmount;
                $totalCost += $lineCostTotal;

                $processedItems[] = [
                    'item_type' => $itemType,
                    'product_variant_id' => $variantId,
                    'description' => $description,
                    'product_stock_id' => $stockId,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'item_discount_mode' => $itemDiscMode,
                    'item_discount_value' => $itemDiscVal,
                    'item_discount_amount' => $itemDiscAmount,
                    'final_unit_price' => $finalUnitPrice,
                    'line_subtotal' => $lineSubtotal,
                    'line_total' => $lineTotal,
                    'discount_type_id' => $item['discount_type_id'] ?? null,
                    'unit_cost' => $unitCost,
                    'line_cost_total' => $lineCostTotal,
                    'line_profit' => $lineProfit,
                    'price_edited' => (bool) ($item['price_edited'] ?? false),
                ];
            }

            $subtotalAfterItemDisc = max(0, $subtotal - $itemDiscountTotal);

            // Universal / Cart Discount
            $univDiscMode = $data['universal_discount_mode'] ?? null;
            $univDiscVal = (float) ($data['universal_discount_value'] ?? 0);
            $univDiscAmount = 0;

            if ($univDiscMode === 'percent') {
                $univDiscAmount = $subtotalAfterItemDisc * ($univDiscVal / 100);
            } elseif ($univDiscMode === 'amount') {
                $univDiscAmount = $univDiscVal;
            }

            $afterUnivDisc = max(0, $subtotalAfterItemDisc - $univDiscAmount);

            // Tax Rate
            $taxRate = (float) ($data['tax_rate'] ?? 0);
            $taxTotal = $afterUnivDisc * ($taxRate / 100);

            $grandTotal = $afterUnivDisc + $taxTotal;
            $paidAmount = (float) ($data['paid_amount'] ?? $grandTotal);
            $changeAmount = max(0, $paidAmount - $grandTotal);

            $paymentStatus = 'paid';
            if ($paidAmount <= 0) {
                $paymentStatus = 'unpaid';
            } elseif ($paidAmount < $grandTotal) {
                $paymentStatus = 'partial';
            }

            $totalProfit = $grandTotal - $totalCost;

            $transaction = Transaction::create([
                'number' => $number,
                'store_id' => $storeId,
                'user_id' => $userId,
                'customer_id' => $data['customer_id'] ?? null,
                'payment_id' => $data['payment_id'] ?? null,
                'transaction_date' => $now,
                'type' => $data['type'] ?? 'retail',
                'service_order_id' => $data['service_order_id'] ?? null,
                'subtotal' => $subtotal,
                'item_discount_total' => $itemDiscountTotal,
                'subtotal_after_item_discount' => $subtotalAfterItemDisc,
                'universal_discount_mode' => $univDiscMode,
                'universal_discount_value' => $univDiscVal,
                'universal_discount_amount' => $univDiscAmount,
                'tax_rate' => $taxRate,
                'tax_total' => $taxTotal,
                'grand_total' => $grandTotal,
                'paid_amount' => $paidAmount,
                'change_amount' => $changeAmount,
                'payment_status' => $paymentStatus,
                'total_cost' => $totalCost,
                'total_profit' => $totalProfit,
                'status' => 'completed',
                'note' => $data['note'] ?? null,
            ]);

            // Save Items & deduct stock
            foreach ($processedItems as $itemRow) {
                $itemRow['transaction_id'] = $transaction->id;
                $itemRow['store_id'] = $storeId;
                TransactionItem::create($itemRow);

                // Deduct stock if part
                if ($itemRow['item_type'] === 'part' && ! empty($itemRow['product_stock_id'])) {
                    $stockModel = ProductStock::find($itemRow['product_stock_id']);
                    if ($stockModel) {
                        $stockModel->decrement('quantity', $itemRow['quantity']);
                    }
                }
            }

            // Link & resolve ServiceOrder status if applicable
            if (! empty($data['service_order_id'])) {
                $serviceOrder = ServiceOrder::find($data['service_order_id']);
                if ($serviceOrder) {
                    $serviceOrder->update([
                        'status' => 'invoiced',
                        'transaction_id' => $transaction->id,
                        'completed_at' => $serviceOrder->completed_at ?: $now,
                    ]);
                }
            }

            return $transaction->fresh(['store', 'user', 'customer', 'payment', 'serviceOrder', 'items.productVariant.product']);
        });
    }

    public function delete(Transaction $transaction): void
    {
        DB::transaction(function () use ($transaction) {
            // Restore product stocks for parts
            foreach ($transaction->items as $item) {
                if ($item->item_type === 'part' && ! empty($item->product_stock_id)) {
                    $stockModel = ProductStock::find($item->product_stock_id);
                    if ($stockModel) {
                        $stockModel->increment('quantity', $item->quantity);
                    }
                }
            }

            // Unlink ServiceOrder if linked
            if ($transaction->service_order_id) {
                $serviceOrder = ServiceOrder::find($transaction->service_order_id);
                if ($serviceOrder && $serviceOrder->transaction_id === $transaction->id) {
                    $serviceOrder->update([
                        'status' => 'ready',
                        'transaction_id' => null,
                    ]);
                }
            }

            $transaction->items()->delete();
            $transaction->delete();
        });
    }

    private function generateNumber(Carbon $date): string
    {
        $prefix = 'POS-'.$date->format('Ymd');
        $countToday = Transaction::whereDate('created_at', $date->toDateString())->count() + 1;

        return $prefix.'-'.str_pad($countToday, 4, '0', STR_PAD_LEFT);
    }
}
