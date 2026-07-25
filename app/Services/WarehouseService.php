<?php

namespace App\Services;

use App\Models\Warehouse;
use App\Repositories\WarehouseRepository;

class WarehouseService
{
    public function __construct(private readonly WarehouseRepository $warehouses) {}

    public function create(array $data): Warehouse
    {
        return $this->warehouses->create($data);
    }

    public function update(Warehouse $warehouse, array $data): Warehouse
    {
        return $this->warehouses->update($warehouse, $data);
    }

    public function delete(Warehouse $warehouse): void
    {
        $this->warehouses->delete($warehouse);
    }
}
