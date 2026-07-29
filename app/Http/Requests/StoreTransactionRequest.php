<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransactionRequest extends FormRequest
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
            'customer_id' => ['nullable', Rule::exists('partners', 'id')],
            'payment_id' => ['nullable', Rule::exists('payments', 'id')],
            'type' => ['required', Rule::in(['retail', 'service', 'internal', 'warranty'])],
            'service_order_id' => ['nullable', Rule::exists('service_orders', 'id')],
            'universal_discount_mode' => ['nullable', Rule::in(['percent', 'amount'])],
            'universal_discount_value' => ['nullable', 'numeric', 'min:0'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'paid_amount' => ['required', 'numeric', 'min:0'],
            'note' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_type' => ['required', Rule::in(['part', 'labor'])],
            'items.*.product_variant_id' => ['nullable', Rule::exists('product_variants', 'id')],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.item_discount_mode' => ['nullable', Rule::in(['percent', 'amount'])],
            'items.*.item_discount_value' => ['nullable', 'numeric', 'min:0'],
            'items.*.discount_type_id' => ['nullable', Rule::exists('discount_types', 'id')],
        ];
    }
}
