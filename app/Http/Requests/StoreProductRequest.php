<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'has_variants' => filter_var($this->input('has_variants', false), FILTER_VALIDATE_BOOLEAN),
            'delete_media_ids' => collect($this->input('delete_media_ids', []))->filter()->values()->all(),
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
            'product_category_id' => ['required', 'uuid', Rule::exists('product_categories', 'id')],
            'brand_id' => ['nullable', 'uuid', Rule::exists('brands', 'id')],
            'unit_id' => ['nullable', 'uuid', Rule::exists('units', 'id')],
            'name' => ['required', 'string', 'max:255'],
            'receipt_name' => ['nullable', 'string', 'max:255'],
            'item_type' => ['required', Rule::in(['part', 'labor'])],
            'has_variants' => ['required', 'boolean'],
            'description' => ['nullable', 'string'],
            'images' => ['nullable', 'array'],
            'images.*' => ['image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'delete_media_ids' => ['nullable', 'array'],
            'delete_media_ids.*' => ['integer', Rule::exists('media', 'id')],
        ];
    }
}
