<?php

namespace App\Services;

use App\Models\PartnerRole;
use App\Repositories\PartnerRoleRepository;

class PartnerRoleService
{
    public function __construct(private readonly PartnerRoleRepository $partnerRoles) {}

    public function create(array $data): PartnerRole
    {
        return $this->partnerRoles->create($data);
    }

    public function update(PartnerRole $partnerRole, array $data): PartnerRole
    {
        return $this->partnerRoles->update($partnerRole, $data);
    }

    public function delete(PartnerRole $partnerRole): void
    {
        $this->partnerRoles->delete($partnerRole);
    }
}
