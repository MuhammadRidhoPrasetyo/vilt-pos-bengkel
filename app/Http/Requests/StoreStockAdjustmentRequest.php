<?php

namespace App\Http\Requests;

use App\Models\Warehouse;
use App\Models\WarehouseLocation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStockAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'store_id' => ['required', 'uuid', Rule::exists('stores', 'id')],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'occurred_at' => ['nullable', 'date'],
            'note' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_variant_id' => ['required', 'uuid', Rule::exists('product_variants', 'id')],
            'items.*.warehouse_id' => ['required', 'uuid', Rule::exists('warehouses', 'id')],
            'items.*.warehouse_location_id' => ['nullable', 'uuid', Rule::exists('warehouse_locations', 'id')],
            'items.*.adjustment_type' => ['required', Rule::in(['increase', 'decrease'])],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
            'items.*.note' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function after(): array
    {
        return [
            function (): void {
                $storeId = $this->input('store_id');

                foreach ($this->input('items', []) as $index => $item) {
                    $warehouse = Warehouse::query()->find($item['warehouse_id'] ?? null);
                    if ($warehouse && $warehouse->store_id !== $storeId) {
                        $this->validator->errors()->add("items.{$index}.warehouse_id", 'Gudang harus berada pada toko dokumen.');
                    }

                    if (! empty($item['warehouse_location_id'])) {
                        $location = WarehouseLocation::query()->find($item['warehouse_location_id']);
                        if ($location && $location->warehouse_id !== ($item['warehouse_id'] ?? null)) {
                            $this->validator->errors()->add("items.{$index}.warehouse_location_id", 'Lokasi harus berada pada gudang yang dipilih.');
                        }
                    }
                }
            },
        ];
    }
}
