<?php

namespace App\Repositories;

use App\Models\Payment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PaymentRepository
{
    public function paginate(?string $search = null): LengthAwarePaginator
    {
        return Payment::query()
            ->when($search, fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('type', 'like', "%{$search}%")
                    ->orWhere('account_number', 'like', "%{$search}%")
                    ->orWhere('account_name', 'like', "%{$search}%")
                    ->orWhere('provider_code', 'like', "%{$search}%");
            }))
            ->latest()
            ->paginate(10)
            ->withQueryString();
    }

    public function create(array $data): Payment
    {
        return Payment::create($data);
    }

    public function update(Payment $payment, array $data): Payment
    {
        $payment->update($data);

        return $payment->refresh();
    }

    public function delete(Payment $payment): void
    {
        $payment->delete();
    }
}
