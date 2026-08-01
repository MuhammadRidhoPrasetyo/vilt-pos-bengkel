<?php

namespace App\Repositories;

use App\Models\Transaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TransactionRepository
{
    public function paginate(
        ?string $search = null,
        ?string $type = null,
        ?string $paymentStatus = null,
        ?string $storeId = null,
        ?string $startDate = null,
        ?string $endDate = null
    ): LengthAwarePaginator {
        return Transaction::query()
            ->with(['store', 'user', 'customer', 'payment', 'serviceOrder', 'items'])
            ->when($storeId, fn ($q) => $q->where('store_id', $storeId))
            ->when($type, fn ($q) => $q->where('type', $type))
            ->when($paymentStatus, fn ($q) => $q->where('payment_status', $paymentStatus))
            ->when($startDate, fn ($q) => $q->whereDate('transaction_date', '>=', $startDate))
            ->when($endDate, fn ($q) => $q->whereDate('transaction_date', '<=', $endDate))
            ->when($search, fn ($q) => $q->where(function ($sq) use ($search) {
                $sq->where('number', 'like', "%{$search}%")
                    ->orWhere('note', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($cq) => $cq->where('name', 'like', "%{$search}%"));
            }))
            ->latest('transaction_date')
            ->latest('created_at')
            ->paginate(15)
            ->withQueryString();
    }

    public function findWithRelations(string $id): Transaction
    {
        return Transaction::query()
            ->with([
                'store',
                'user',
                'customer',
                'payment',
                'serviceOrder.vehicle',
                'items.productVariant.product',
                'items.discountType',
                'items.batches.inventoryBatch.warehouse',
                'paymentAttempts.payment',
                'paymentAttempts.user',
            ])
            ->findOrFail($id);
    }
}
