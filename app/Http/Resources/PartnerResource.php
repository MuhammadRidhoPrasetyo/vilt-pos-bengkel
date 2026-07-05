<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PartnerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'linked_store_id' => $this->linked_store_id,
            'store' => $this->whenLoaded('store', fn () => $this->store ? ['id' => $this->store->id, 'name' => $this->store->name] : null),
            'linked_store' => $this->whenLoaded('linkedStore', fn () => $this->linkedStore ? ['id' => $this->linkedStore->id, 'name' => $this->linkedStore->name] : null),
            'code' => $this->code,
            'name' => $this->name,
            'kind' => $this->kind,
            'contact_person' => $this->contact_person,
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
            'city' => $this->city,
            'province' => $this->province,
            'postal_code' => $this->postal_code,
            'npwp' => $this->npwp,
            'bank_name' => $this->bank_name,
            'bank_account' => $this->bank_account,
            'is_active' => $this->is_active,
            'roles' => PartnerRoleResource::collection($this->whenLoaded('roles')),
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
