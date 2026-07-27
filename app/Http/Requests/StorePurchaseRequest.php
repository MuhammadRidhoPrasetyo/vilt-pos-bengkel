<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePurchaseRequest extends FormRequest
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
            'store_id' => ['required', Rule::exists('stores', 'id')],
            'supplier_id' => ['required', Rule::exists('partners', 'id')],
            'purchase_date' => ['required', 'date'],
            'invoice_number' => ['nullable', 'string', 'max:255'],
            'discount_type' => ['nullable', Rule::in(['percent', 'amount'])],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_variant_id' => ['required', Rule::exists('product_variants', 'id')],
            'items.*.price_type' => ['required', Rule::in(['toko', 'distributor'])],
            'items.*.quantity_ordered' => ['required', 'integer', 'min:1'],
            'items.*.unit_purchase_price' => ['required', 'numeric', 'min:0'],
            'items.*.item_discount_type' => ['nullable', Rule::in(['percent', 'amount'])],
            'items.*.item_discount_value' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
