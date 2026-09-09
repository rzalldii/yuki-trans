<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
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
            'from_wallet_id' => ['required', Rule::exists('finance_wallets', 'id')->whereNull('deleted_at')],
            'to_wallet_id' => ['required', Rule::exists('finance_wallets', 'id')->whereNull('deleted_at'), 'different:from_wallet_id'],
            'amount' => ['required', 'numeric', 'min:1'],
            'description' => 'nullable|string|max:1000',
            'transaction_date' => 'required|date',
            'tags' => 'nullable|array|max:10',
            'tags.*' => 'string|max:50',
        ];
    }
}