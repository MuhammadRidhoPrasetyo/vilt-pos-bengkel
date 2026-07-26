<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductVariantRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'attribute_option_ids' => collect($this->input('attribute_option_ids', []))
                ->filter()
                ->values()
                ->all(),
            'delete_media_ids' => collect($this->input('delete_media_ids', []))
                ->filter()
                ->values()
                ->all(),
            'is_active' => filter_var($this->input('is_active', true), FILTER_VALIDATE_BOOLEAN),
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'product_id' => ['required', 'uuid', Rule::exists('products', 'id')],
            'sku' => ['nullable', 'string', 'max:255', Rule::unique('product_variants', 'sku')],
            'barcode' => ['nullable', 'string', 'max:255', Rule::unique('product_variants', 'barcode')],
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
