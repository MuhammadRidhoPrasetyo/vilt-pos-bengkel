<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WarehouseResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'phone' => $this->phone,
            'is_active' => $this->is_active,
            'store' => $this->whenLoaded('store', fn () => [
                'id' => $this->store?->id,
                'name' => $this->store?->name,
            ]),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
