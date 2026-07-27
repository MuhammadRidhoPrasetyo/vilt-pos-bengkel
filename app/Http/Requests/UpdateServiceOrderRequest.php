<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateServiceOrderRequest extends FormRequest
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
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:50'],
            'vehicle_id' => ['nullable', Rule::exists('vehicles', 'id')],
            'plate_number' => ['required', 'string', 'max:50'],
            'vehicle_brand' => ['nullable', 'string', 'max:100'],
            'vehicle_model' => ['nullable', 'string', 'max:100'],
            'year' => ['nullable', 'integer', 'min:1900', 'max:'.(date('Y') + 1)],
            'color' => ['nullable', 'string', 'max:50'],
            'odometer' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', Rule::in(['checkin', 'diagnosis', 'in_progress', 'waiting_parts', 'ready', 'invoiced', 'cancelled'])],
            'general_complaint' => ['nullable', 'string'],
            'diagnosis' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['nullable', 'string'],
            'items.*.item_type' => ['required', Rule::in(['part', 'labor'])],
            'items.*.product_variant_id' => ['nullable', Rule::exists('product_variants', 'id')],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.mechanic_id' => ['nullable', Rule::exists('users', 'id')],
        ];
    }
}
