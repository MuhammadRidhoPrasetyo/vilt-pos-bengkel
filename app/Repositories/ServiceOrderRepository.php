<?php

namespace App\Repositories;

use App\Models\ServiceOrder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ServiceOrderRepository
{
    public function paginate(
        ?string $search = null,
        ?string $status = null,
        ?string $storeId = null,
        ?string $startDate = null,
        ?string $endDate = null
    ): LengthAwarePaginator {
        return ServiceOrder::query()
            ->with(['store', 'customer', 'items.mechanic', 'items.productVariant.product'])
            ->when($storeId, fn ($q) => $q->where('store_id', $storeId))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($startDate, fn ($q) => $q->whereDate('checkin_at', '>=', $startDate))
            ->when($endDate, fn ($q) => $q->whereDate('checkin_at', '<=', $endDate))
            ->when($search, fn ($q) => $q->where(function ($sq) use ($search) {
                $sq->where('number', 'like', "%{$search}%")
                    ->orWhere('plate_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('vehicle_model', 'like', "%{$search}%")
                    ->orWhere('general_complaint', 'like', "%{$search}%");
            }))
            ->latest('checkin_at')
            ->latest('created_at')
            ->paginate(10)
            ->withQueryString();
    }

    public function findWithRelations(string $id): ServiceOrder
    {
        return ServiceOrder::query()
            ->with(['store', 'customer', 'vehicle', 'items.mechanic', 'items.productVariant.product'])
            ->findOrFail($id);
    }

    public function getActiveOrders(?string $storeId = null)
    {
        return ServiceOrder::query()
            ->with(['store', 'customer', 'items.mechanic', 'items.productVariant.product'])
            ->when($storeId, fn ($q) => $q->where('store_id', $storeId))
            ->whereIn('status', ['checkin', 'in_progress', 'waiting_parts', 'ready'])
            ->orderBy('checkin_at', 'asc')
            ->get();
    }
}
