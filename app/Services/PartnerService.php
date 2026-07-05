<?php

namespace App\Services;

use App\Models\Partner;
use App\Repositories\PartnerRepository;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class PartnerService
{
    public function __construct(private readonly PartnerRepository $partners) {}

    public function create(array $data): Partner
    {
        return DB::transaction(function () use ($data) {
            $partner = $this->partners->create(Arr::except($data, 'roles'));
            $partner->roles()->sync($data['roles'] ?? []);

            return $partner->load(['store:id,name', 'linkedStore:id,name', 'roles:id,name']);
        });
    }

    public function update(Partner $partner, array $data): Partner
    {
        return DB::transaction(function () use ($partner, $data) {
            $partner = $this->partners->update($partner, Arr::except($data, 'roles'));
            $partner->roles()->sync($data['roles'] ?? []);

            return $partner->load(['store:id,name', 'linkedStore:id,name', 'roles:id,name']);
        });
    }

    public function delete(Partner $partner): void
    {
        $this->partners->delete($partner);
    }
}
