<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePrinterRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'connection_type' => ['required', Rule::in(['usb', 'network', 'bluetooth'])],
            'address' => ['required', 'string', 'max:255'],
            'is_default' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_default' => $this->boolean('is_default'),
        ]);
    }
}
