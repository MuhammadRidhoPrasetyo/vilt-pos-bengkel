<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PrinterResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'store' => new StoreResource($this->whenLoaded('store')),
            'name' => $this->name,
            'connection_type' => $this->connection_type,
            'address' => $this->address,
            'is_default' => (bool) $this->is_default,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
