<?php

namespace App\Repositories;

use App\Models\Purchase;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PurchaseRepository
{
    public function paginate(?string $search = null, ?string $storeId = null, ?string $supplierId = null, ?string $startDate = null, ?string $endDate = null): LengthAwarePaginator
    {
        return Purchase::query()
            ->with(['store', 'supplier', 'creator', 'items.productVariant.product'])
            ->when($storeId, fn ($query) => $query->where('store_id', $storeId))
            ->when($supplierId, fn ($query) => $query->where('supplier_id', $supplierId))
            ->when($startDate, fn ($query) => $query->whereDate('purchase_date', '>=', $startDate))
            ->when($endDate, fn ($query) => $query->whereDate('purchase_date', '<=', $endDate))
            ->when($search, fn ($query) => $query->where(function ($q) use ($search) {
                $q->where('number', 'like', "%{$search}%")
                    ->orWhere('invoice_number', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('supplier', fn ($sq) => $sq->where('name', 'like', "%{$search}%"));
            }))
            ->latest('purchase_date')
            ->latest('created_at')
            ->paginate(10)
            ->withQueryString();
    }

    public function findWithRelations(string $id): Purchase
    {
        return Purchase::query()
            ->with(['store', 'supplier', 'creator', 'receiver', 'items.productVariant.product', 'items.inventoryBatches', 'cashFlows.category'])
            ->findOrFail($id);
    }
}
