<?php

namespace App\Services;

use App\Models\Payment;
use App\Repositories\PaymentRepository;

class PaymentService
{
    public function __construct(private readonly PaymentRepository $payments) {}

    public function create(array $data): Payment
    {
        return $this->payments->create($data);
    }

    public function update(Payment $payment, array $data): Payment
    {
        return $this->payments->update($payment, $data);
    }

    public function delete(Payment $payment): void
    {
        $this->payments->delete($payment);
    }
}
