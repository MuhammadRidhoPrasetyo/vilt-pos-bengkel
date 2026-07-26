<?php

namespace App\Http\Controllers;

use App\Models\ProductPrice;
use App\Services\ProductPriceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductPriceController extends Controller
{
    public function __construct(
        private readonly ProductPriceService $service
    ) {}

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_variant_id' => ['required', 'uuid', Rule::exists('product_variants', 'id')],
            'store_id' => ['nullable', 'uuid', Rule::exists('stores', 'id')],
            'purchase_price' => ['required', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'markup' => ['nullable', 'numeric', 'min:0'],
            'markup_type' => ['nullable', Rule::in(['percent', 'amount'])],
            'is_active' => ['boolean'],
        ]);

        $this->service->setPrice($validated);

        return redirect()->back()->with('success', 'Harga per toko berhasil dikonfigurasikan.');
    }

    public function update(Request $request, ProductPrice $productPrice): RedirectResponse
    {
        $validated = $request->validate([
            'product_variant_id' => ['required', 'uuid', Rule::exists('product_variants', 'id')],
            'store_id' => ['nullable', 'uuid', Rule::exists('stores', 'id')],
            'purchase_price' => ['required', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'markup' => ['nullable', 'numeric', 'min:0'],
            'markup_type' => ['nullable', Rule::in(['percent', 'amount'])],
            'is_active' => ['boolean'],
        ]);

        $this->service->setPrice($validated, $productPrice->id);

        return redirect()->back()->with('success', 'Harga per toko berhasil diperbarui.');
    }

    public function destroy(ProductPrice $productPrice): RedirectResponse
    {
        $this->service->deletePrice($productPrice);

        return redirect()->back()->with('success', 'Harga per toko berhasil dihapus.');
    }
}
