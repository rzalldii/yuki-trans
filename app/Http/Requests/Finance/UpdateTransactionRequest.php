<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('description') && is_string($this->description)) {
            $this->merge([
                'description' => trim($this->description),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'wallet_id' => ['required', Rule::exists('finance_wallets', 'id')->whereNull('deleted_at')],
            'category_id' => ['required', Rule::exists('finance_categories', 'id')->whereNull('deleted_at')],
            'amount' => ['required', 'numeric', 'min:1'],
            'description' => ['nullable', 'string', 'max:1000'],
            'transaction_date' => ['required', 'date'],
            'tags' => ['nullable', 'array', 'max:10'],
            'tags.*' => ['string', 'max:50'],
        ];
    }
}