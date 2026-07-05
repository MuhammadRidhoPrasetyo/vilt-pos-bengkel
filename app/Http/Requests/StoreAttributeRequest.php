<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAttributeRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'options' => collect($this->input('options', []))->map(fn ($option) => trim((string) $option))->filter()->values()->all(),
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
            'name' => ['required', 'string', 'max:255'],
            'options' => ['array'],
            'options.*' => ['nullable', 'string', 'max:255'],
        ];
    }
}
