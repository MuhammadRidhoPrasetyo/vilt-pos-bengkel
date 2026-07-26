<?php

namespace App\Http\Controllers;

use App\Models\Attribute;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProductAttributeController extends Controller
{
    public function store(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'options' => ['required', 'array', 'min:1'],
            'options.*' => ['required', 'string', 'max:255'],
        ]);

        $attribute = $product->attributes()->create([
            'name' => $validated['name'],
        ]);

        foreach ($validated['options'] as $value) {
            $attribute->options()->create(['value' => trim($value)]);
        }

        return redirect()->back()->with('success', 'Atribut produk berhasil ditambahkan.');
    }

    public function update(Request $request, Attribute $attribute): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'options' => ['required', 'array', 'min:1'],
            'options.*' => ['required', 'string', 'max:255'],
        ]);

        $attribute->update(['name' => $validated['name']]);

        // Replace options
        $attribute->options()->delete();
        foreach ($validated['options'] as $value) {
            $attribute->options()->create(['value' => trim($value)]);
        }

        return redirect()->back()->with('success', 'Atribut produk berhasil diperbarui.');
    }

    public function destroy(Attribute $attribute): RedirectResponse
    {
        $attribute->delete();

        return redirect()->back()->with('success', 'Atribut produk berhasil dihapus.');
    }
}
