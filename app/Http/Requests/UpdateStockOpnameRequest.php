<?php

namespace App\Http\Requests;

use App\Models\WarehouseLocation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStockOpnameRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'opname_number' => ['nullable', 'string', 'max:255'],
            'occurred_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_variant_id' => ['required', 'uuid', Rule::exists('product_variants', 'id')],
            'items.*.warehouse_location_id' => ['nullable', 'uuid', Rule::exists('warehouse_locations', 'id')],
            'items.*.system_quantity' => ['required', 'integer', 'min:0'],
            'items.*.physical_quantity' => ['required', 'integer', 'min:0'],
            'items.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
            'items.*.note' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function after(): array
    {
        return [
            function (): void {
                $opname = $this->route('stock_opname');
                $warehouseId = $opname?->warehouse_id;

                if (! $warehouseId) {
                    return;
                }

                foreach ($this->input('items', []) as $index => $item) {
                    if (! empty($item['warehouse_location_id'])) {
                        $location = WarehouseLocation::query()->find($item['warehouse_location_id']);
                        if ($location && $location->warehouse_id !== $warehouseId) {
                            $this->validator->errors()->add("items.{$index}.warehouse_location_id", 'Lokasi harus berada pada gudang dokumen.');
                        }
                    }
                }
            },
        ];
    }
}
