<?php

namespace App\Services;

use App\Models\WarehouseLocation;
use App\Repositories\WarehouseLocationRepository;

class WarehouseLocationService
{
    public function __construct(private readonly WarehouseLocationRepository $warehouseLocations) {}

    public function create(array $data): WarehouseLocation
    {
        return $this->warehouseLocations->create($data);
    }

    public function update(WarehouseLocation $warehouseLocation, array $data): WarehouseLocation
    {
        return $this->warehouseLocations->update($warehouseLocation, $data);
    }

    public function delete(WarehouseLocation $warehouseLocation): void
    {
        $this->warehouseLocations->delete($warehouseLocation);
    }
}
