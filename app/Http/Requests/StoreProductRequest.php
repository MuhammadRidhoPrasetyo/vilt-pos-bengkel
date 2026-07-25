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
            'item_type' => ['required', Rule::in(['part', 'labor'])],
            'has_variants' => ['required', 'boolean'],
            'description' => ['nullable', 'string'],
        ];
    }
}
