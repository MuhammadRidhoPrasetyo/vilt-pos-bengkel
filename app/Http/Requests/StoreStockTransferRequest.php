<?php

namespace App\Http\Requests;

use App\Models\Warehouse;
use App\Models\WarehouseLocation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStockTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from_store_id' => ['required', 'uuid', Rule::exists('stores', 'id')],
            'to_store_id' => ['required', 'uuid', 'different:from_store_id', Rule::exists('stores', 'id')],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'occurred_at' => ['nullable', 'date'],
            'note' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_variant_id' => ['required', 'uuid', Rule::exists('product_variants', 'id')],
            'items.*.from_warehouse_id' => ['required', 'uuid', Rule::exists('warehouses', 'id')],
            'items.*.from_warehouse_location_id' => ['nullable', 'uuid', Rule::exists('warehouse_locations', 'id')],
            'items.*.to_warehouse_id' => ['required', 'uuid', Rule::exists('warehouses', 'id')],
            'items.*.to_warehouse_location_id' => ['nullable', 'uuid', Rule::exists('warehouse_locations', 'id')],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
            'items.*.product_price_id' => ['nullable', 'uuid', Rule::exists('product_prices', 'id')],
        ];
    }

    public function after(): array
    {
        return [
            function (): void {
                foreach ($this->input('items', []) as $index => $item) {
                    $fromWarehouse = Warehouse::query()->find($item['from_warehouse_id'] ?? null);
                    $toWarehouse = Warehouse::query()->find($item['to_warehouse_id'] ?? null);

                    if ($fromWarehouse && $fromWarehouse->store_id !== $this->input('from_store_id')) {
                        $this->validator->errors()->add("items.{$index}.from_warehouse_id", 'Gudang asal harus berada pada toko asal.');
                    }

                    if ($toWarehouse && $toWarehouse->store_id !== $this->input('to_store_id')) {
                        $this->validator->errors()->add("items.{$index}.to_warehouse_id", 'Gudang tujuan harus berada pada toko tujuan.');
                    }

                    $sameWarehouse = ($item['from_warehouse_id'] ?? null) === ($item['to_warehouse_id'] ?? null);
                    $sameLocation = ($item['from_warehouse_location_id'] ?? null) === ($item['to_warehouse_location_id'] ?? null);
                    if ($sameWarehouse && $sameLocation) {
                        $this->validator->errors()->add("items.{$index}.to_warehouse_id", 'Gudang/lokasi tujuan tidak boleh sama dengan asal.');
                    }

                    foreach (['from' => 'from_warehouse', 'to' => 'to_warehouse'] as $prefix => $field) {
                        $locationId = $item["{$field}_location_id"] ?? null;
                        if (! $locationId) {
                            continue;
                        }

                        $location = WarehouseLocation::query()->find($locationId);
                        if ($location && $location->warehouse_id !== ($item["{$field}_id"] ?? null)) {
                            $this->validator->errors()->add("items.{$index}.{$field}_location_id", 'Lokasi harus berada pada gudang yang dipilih.');
                        }
                    }
                }
            },
        ];
    }
}
