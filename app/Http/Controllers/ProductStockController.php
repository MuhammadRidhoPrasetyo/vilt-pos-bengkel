<?php

namespace App\Http\Controllers;

use App\Models\ProductStock;
use App\Services\ProductStockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductStockController extends Controller
{
    public function __construct(
        private readonly ProductStockService $service
    ) {}

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_variant_id' => ['required', 'uuid', Rule::exists('product_variants', 'id')],
            'warehouse_id' => ['required', 'uuid', Rule::exists('warehouses', 'id')],
            'warehouse_location_id' => ['nullable', 'uuid', Rule::exists('warehouse_locations', 'id')],
            'quantity' => ['required', 'integer', 'min:0'],
            'minimum_stock' => ['required', 'integer', 'min:0'],
            'is_hidden' => ['boolean'],
        ]);

        $this->service->setStock($validated);

        return redirect()->back()->with('success', 'Stok gudang berhasil diinisialisasi.');
    }

    public function update(Request $request, ProductStock $productStock): RedirectResponse
    {
        $validated = $request->validate([
            'product_variant_id' => ['required', 'uuid', Rule::exists('product_variants', 'id')],
            'warehouse_id' => ['required', 'uuid', Rule::exists('warehouses', 'id')],
            'warehouse_location_id' => ['nullable', 'uuid', Rule::exists('warehouse_locations', 'id')],
            'quantity' => ['required', 'integer', 'min:0'],
            'minimum_stock' => ['required', 'integer', 'min:0'],
            'is_hidden' => ['boolean'],
        ]);

        $this->service->setStock($validated, $productStock->id);

        return redirect()->back()->with('success', 'Stok gudang berhasil diperbarui.');
    }

    public function destroy(ProductStock $productStock): RedirectResponse
    {
        $this->service->deleteStock($productStock);

        return redirect()->back()->with('success', 'Stok gudang berhasil dihapus.');
    }
}
