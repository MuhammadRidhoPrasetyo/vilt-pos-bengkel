<?php

namespace App\Services;

use App\Models\Unit;
use App\Repositories\UnitRepository;

class UnitService
{
    public function __construct(private readonly UnitRepository $units) {}

    public function create(array $data): Unit
    {
        return $this->units->create($data);
    }

    public function update(Unit $unit, array $data): Unit
    {
        return $this->units->update($unit, $data);
    }

    public function delete(Unit $unit): void
    {
        $this->units->delete($unit);
    }
}
