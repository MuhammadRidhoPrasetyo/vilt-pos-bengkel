<?php

namespace App\Services;

use App\Models\Brand;
use App\Repositories\BrandRepository;

class BrandService
{
    public function __construct(private readonly BrandRepository $brands) {}

    public function create(array $data): Brand
    {
        return $this->brands->create($data);
    }

    public function update(Brand $brand, array $data): Brand
    {
        return $this->brands->update($brand, $data);
    }

    public function delete(Brand $brand): void
    {
        $this->brands->delete($brand);
    }
}
