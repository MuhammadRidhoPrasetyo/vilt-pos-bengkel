<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateProductVariantRequest extends StoreProductVariantRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $productVariant = $this->route('product_variant');

        return [
            'product_id' => ['required', 'uuid', Rule::exists('products', 'id')],
            'sku' => ['nullable', 'string', 'max:255', Rule::unique('product_variants', 'sku')->ignore($productVariant)],
            'barcode' => ['nullable', 'string', 'max:255', Rule::unique('product_variants', 'barcode')->ignore($productVariant)],
            'name_suffix' => ['nullable', 'string', 'max:255'],
            'receipt_name' => ['nullable', 'string', 'max:255'],
            'default_purchase_price' => ['required', 'numeric', 'min:0'],
            'default_selling_price' => ['required', 'numeric', 'min:0'],
            'is_active' => ['required', 'boolean'],
            'attribute_option_ids' => ['array'],
            'attribute_option_ids.*' => ['required', 'uuid', Rule::exists('attribute_options', 'id')],
            'images' => ['nullable', 'array'],
            'images.*' => ['image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'delete_media_ids' => ['nullable', 'array'],
            'delete_media_ids.*' => ['integer', Rule::exists('media', 'id')],
        ];
    }
}
