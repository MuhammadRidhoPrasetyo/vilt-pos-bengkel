<?php

namespace App\Services;

use App\Models\DiscountType;
use App\Repositories\DiscountTypeRepository;

class DiscountTypeService
{
    public function __construct(private readonly DiscountTypeRepository $discountTypes) {}

    public function create(array $data): DiscountType
    {
        return $this->discountTypes->create($data);
    }

    public function update(DiscountType $discountType, array $data): DiscountType
    {
        return $this->discountTypes->update($discountType, $data);
    }

    public function delete(DiscountType $discountType): void
    {
        $this->discountTypes->delete($discountType);
    }
}
