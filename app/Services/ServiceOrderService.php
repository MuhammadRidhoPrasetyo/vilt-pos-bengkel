<?php

namespace App\Services;

use App\Models\ServiceOrder;
use App\Models\ServiceOrderItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ServiceOrderService
{
    public function create(array $data): ServiceOrder
    {
        return DB::transaction(function () use ($data) {
            $now = now();
            $number = $this->generateNumber($now);

            $estimatedTotal = 0;
            foreach ($data['items'] as $item) {
                $qty = (int) $item['quantity'];
                $price = (float) $item['unit_price'];
                $estimatedTotal += ($qty * $price);
            }

            $serviceOrder = ServiceOrder::create([
                'number' => $number,
                'store_id' => $data['store_id'],
                'customer_id' => $data['customer_id'] ?? null,
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'] ?? null,
                'vehicle_id' => $data['vehicle_id'] ?? null,
                'plate_number' => strtoupper(trim($data['plate_number'])),
                'vehicle_brand' => $data['vehicle_brand'] ?? null,
                'vehicle_model' => $data['vehicle_model'] ?? null,
                'year' => $data['year'] ?? null,
                'color' => $data['color'] ?? null,
                'odometer' => $data['odometer'] ?? null,
                'status' => $data['status'] ?? 'checkin',
                'checkin_at' => $now,
                'completed_at' => ($data['status'] ?? '') === 'ready' || ($data['status'] ?? '') === 'invoiced' ? $now : null,
                'general_complaint' => $data['general_complaint'] ?? null,
                'diagnosis' => $data['diagnosis'] ?? null,
                'estimated_total' => $estimatedTotal,
            ]);

            foreach ($data['items'] as $item) {
                $qty = (int) $item['quantity'];
                $price = (float) $item['unit_price'];
                $lineTotal = $qty * $price;

                ServiceOrderItem::create([
                    'service_order_id' => $serviceOrder->id,
                    'item_type' => $item['item_type'],
                    'product_variant_id' => $item['product_variant_id'] ?? null,
                    'description' => $item['description'],
                    'quantity' => $qty,
                    'unit_price' => $price,
                    'line_total' => $lineTotal,
                    'mechanic_id' => $item['item_type'] === 'labor' ? ($item['mechanic_id'] ?? null) : null,
                    'assigned_at' => ! empty($item['mechanic_id']) ? $now : null,
                ]);
            }

            return $serviceOrder->load(['items.mechanic', 'items.productVariant.product']);
        });
    }

    public function update(ServiceOrder $serviceOrder, array $data): ServiceOrder
    {
        return DB::transaction(function () use ($serviceOrder, $data) {
            $now = now();
            $estimatedTotal = 0;

            foreach ($data['items'] as $item) {
                $qty = (int) $item['quantity'];
                $price = (float) $item['unit_price'];
                $estimatedTotal += ($qty * $price);
            }

            $completedAt = $serviceOrder->completed_at;
            if ($data['status'] === 'ready' || $data['status'] === 'invoiced') {
                $completedAt = $completedAt ?: $now;
            } else {
                $completedAt = null;
            }

            $serviceOrder->update([
                'store_id' => $data['store_id'],
                'customer_id' => $data['customer_id'] ?? null,
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'] ?? null,
                'vehicle_id' => $data['vehicle_id'] ?? null,
                'plate_number' => strtoupper(trim($data['plate_number'])),
                'vehicle_brand' => $data['vehicle_brand'] ?? null,
                'vehicle_model' => $data['vehicle_model'] ?? null,
                'year' => $data['year'] ?? null,
                'color' => $data['color'] ?? null,
                'odometer' => $data['odometer'] ?? null,
                'status' => $data['status'],
                'completed_at' => $completedAt,
                'general_complaint' => $data['general_complaint'] ?? null,
                'diagnosis' => $data['diagnosis'] ?? null,
                'estimated_total' => $estimatedTotal,
            ]);

            // Sync items
            $existingItemIds = $serviceOrder->items()->pluck('id')->toArray();
            $updatedItemIds = [];

            foreach ($data['items'] as $item) {
                $qty = (int) $item['quantity'];
                $price = (float) $item['unit_price'];
                $lineTotal = $qty * $price;
                $mechanicId = $item['item_type'] === 'labor' ? ($item['mechanic_id'] ?? null) : null;

                if (! empty($item['id']) && in_array($item['id'], $existingItemIds)) {
                    $existingItem = ServiceOrderItem::find($item['id']);
                    $existingItem->update([
                        'item_type' => $item['item_type'],
                        'product_variant_id' => $item['product_variant_id'] ?? null,
                        'description' => $item['description'],
                        'quantity' => $qty,
                        'unit_price' => $price,
                        'line_total' => $lineTotal,
                        'mechanic_id' => $mechanicId,
                        'assigned_at' => $mechanicId ? ($existingItem->assigned_at ?: $now) : null,
                    ]);
                    $updatedItemIds[] = $existingItem->id;
                } else {
                    $newItem = ServiceOrderItem::create([
                        'service_order_id' => $serviceOrder->id,
                        'item_type' => $item['item_type'],
                        'product_variant_id' => $item['product_variant_id'] ?? null,
                        'description' => $item['description'],
                        'quantity' => $qty,
                        'unit_price' => $price,
                        'line_total' => $lineTotal,
                        'mechanic_id' => $mechanicId,
                        'assigned_at' => $mechanicId ? $now : null,
                    ]);
                    $updatedItemIds[] = $newItem->id;
                }
            }

            // Remove items no longer in input
            $itemsToDelete = array_diff($existingItemIds, $updatedItemIds);
            if (! empty($itemsToDelete)) {
                ServiceOrderItem::whereIn('id', $itemsToDelete)->delete();
            }

            return $serviceOrder->fresh(['items.mechanic', 'items.productVariant.product']);
        });
    }

    public function delete(ServiceOrder $serviceOrder): void
    {
        DB::transaction(function () use ($serviceOrder) {
            $serviceOrder->items()->delete();
            $serviceOrder->delete();
        });
    }

    public function updateStatus(ServiceOrder $serviceOrder, string $status, int|string|null $mechanicId = null): ServiceOrder
    {
        return DB::transaction(function () use ($serviceOrder, $status, $mechanicId) {
            $now = now();
            $completedAt = $serviceOrder->completed_at;

            if ($status === 'ready' || $status === 'invoiced') {
                $completedAt = $completedAt ?: $now;
            } else {
                $completedAt = null;
            }

            $serviceOrder->update([
                'status' => $status,
                'completed_at' => $completedAt,
            ]);

            if ($mechanicId !== null) {
                $laborItems = $serviceOrder->items()->where('item_type', 'labor')->get();
                if ($laborItems->isNotEmpty()) {
                    foreach ($laborItems as $item) {
                        $item->update([
                            'mechanic_id' => $mechanicId,
                            'assigned_at' => $item->assigned_at ?: $now,
                        ]);
                    }
                }
            }

            return $serviceOrder->fresh(['store', 'customer', 'items.mechanic', 'items.productVariant.product']);
        });
    }

    private function generateNumber(Carbon $date): string
    {
        $prefix = 'SO-'.$date->format('Ymd');
        $countToday = ServiceOrder::whereDate('created_at', $date->toDateString())->count() + 1;

        return $prefix.'-'.str_pad($countToday, 4, '0', STR_PAD_LEFT);
    }
}
