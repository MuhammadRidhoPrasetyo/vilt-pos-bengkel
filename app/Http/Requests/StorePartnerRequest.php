<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePartnerRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'roles' => $this->input('roles', []),
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
            'store_id' => ['nullable', Rule::exists('stores', 'id')],
            'linked_store_id' => ['nullable', Rule::exists('stores', 'id')],
            'code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('partners', 'code')->where(fn ($query) => $query->where('store_id', $this->input('store_id'))),
            ],
            'name' => ['required', 'string', 'max:255'],
            'kind' => ['required', Rule::in(['person', 'organization'])],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:255'],
            'npwp' => ['nullable', 'string', 'max:255'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_account' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
            'roles' => ['array'],
            'roles.*' => [Rule::exists('partner_roles', 'id')],
            'notes' => ['nullable', 'string'],
        ];
    }
}
