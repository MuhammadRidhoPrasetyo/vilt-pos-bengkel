<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttributeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'store' => $this->whenLoaded('store', fn () => $this->store ? ['id' => $this->store->id, 'name' => $this->store->name] : ['id' => null, 'name' => 'Global (Semua Toko)']),
            'name' => $this->name,
            'options' => $this->whenLoaded('options', fn () => $this->options->pluck('value')->values()),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
