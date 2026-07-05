<?php

namespace App\Services;

use App\Models\Store;
use App\Repositories\StoreRepository;

class StoreService
{
    public function __construct(private readonly StoreRepository $stores) {}

    public function create(array $data): Store
    {
        return $this->stores->create($data);
    }

    public function update(Store $store, array $data): Store
    {
        return $this->stores->update($store, $data);
    }

    public function delete(Store $store): void
    {
        $this->stores->delete($store);
    }
}
