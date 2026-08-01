<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransactionPaymentAttemptRequest extends FormRequest
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
            'payment_id' => ['nullable', Rule::exists('payments', 'id')],
            'amount_given' => ['required', 'numeric', 'min:1'],
            'paid_at' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
