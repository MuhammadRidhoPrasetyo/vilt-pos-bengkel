<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WarehouseLocationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'warehouse_id' => $this->warehouse_id,
            'parent_id' => $this->parent_id,
            'type' => $this->type,
            'type_label' => match ($this->type) {
                'rack', 'rak' => 'Rak Susun',
                'shelf', 'ambalan' => 'Ambalan / Tingkat Rak',
                'bin', 'laci' => 'Kotak Part / Laci Baut',
                'showcase', 'etalase' => 'Etalase Kaca',
                'hanging', 'gantung' => 'Rak Gantung',
                'floor', 'lantai' => 'Lantai / Palet',
                'cabinet', 'lemari' => 'Lemari / Locker',
                'zone', 'zona' => 'Zona / Area Gudang',
                'staging', 'transit' => 'Area Transit / Bongkar Muat',
                default => ucfirst((string) $this->type),
            },
            'code' => $this->code,
            'name' => $this->name,
            'full_path' => $this->full_path,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'warehouse' => $this->whenLoaded('warehouse', fn () => [
                'id' => $this->warehouse?->id,
                'name' => $this->warehouse?->name,
            ]),
            'parent' => $this->whenLoaded('parent', fn () => $this->parent ? [
                'id' => $this->parent->id,
                'name' => $this->parent->name,
            ] : null),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
