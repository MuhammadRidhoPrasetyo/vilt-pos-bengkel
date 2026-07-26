<?php

namespace App\Http\Controllers;

use App\Models\ProductDiscount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductDiscountController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_variant_id' => ['required', 'uuid', Rule::exists('product_variants', 'id')],
            'store_id' => ['nullable', 'uuid', Rule::exists('stores', 'id')],
            'discount_type_id' => ['required', 'uuid', Rule::exists('discount_types', 'id')],
            'type' => ['required', Rule::in(['percent', 'amount'])],
            'value' => ['required', 'numeric', 'min:0'],
        ]);

        ProductDiscount::create($validated);

        return redirect()->back()->with('success', 'Promo & diskon berhasil ditambahkan.');
    }

    public function update(Request $request, ProductDiscount $productDiscount): RedirectResponse
    {
        $validated = $request->validate([
            'product_variant_id' => ['required', 'uuid', Rule::exists('product_variants', 'id')],
            'store_id' => ['nullable', 'uuid', Rule::exists('stores', 'id')],
            'discount_type_id' => ['required', 'uuid', Rule::exists('discount_types', 'id')],
            'type' => ['required', Rule::in(['percent', 'amount'])],
            'value' => ['required', 'numeric', 'min:0'],
        ]);

        $productDiscount->update($validated);

        return redirect()->back()->with('success', 'Promo & diskon berhasil diperbarui.');
    }

    public function destroy(ProductDiscount $productDiscount): RedirectResponse
    {
        $productDiscount->delete();

        return redirect()->back()->with('success', 'Promo & diskon berhasil dihapus.');
    }
}
