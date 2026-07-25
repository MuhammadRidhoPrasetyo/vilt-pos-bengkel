<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductCategoryRequest extends FormRequest
{
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
            'store_id' => ['nullable', 'uuid', Rule::exists('stores', 'id')],
            'parent_id' => ['nullable', 'uuid', Rule::exists('product_categories', 'id')],
            'name' => ['required', 'string', 'max:255'],
            'pricing_mode' => ['required', Rule::in(['fixed', 'editable'])],
        ];
    }
}
