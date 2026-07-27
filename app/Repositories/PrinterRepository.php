<?php

namespace App\Repositories;

use App\Models\Printer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PrinterRepository
{
    public function paginate(?string $search = null, ?string $storeId = null): LengthAwarePaginator
    {
        return Printer::query()
            ->with(['store'])
            ->when($storeId, fn ($query) => $query->where('store_id', $storeId))
            ->when($search, fn ($query) => $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%")
                    ->orWhere('connection_type', 'like', "%{$search}%");
            }))
            ->orderByDesc('is_default')
            ->latest()
            ->paginate(10)
            ->withQueryString();
    }

    public function find(string $id): Printer
    {
        return Printer::query()->with('store')->findOrFail($id);
    }
}
