<?php

declare(strict_types=1);

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWalletRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('name') && is_string($this->name)) {
            $this->merge([
                'name' => trim($this->name),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('finance_wallets')->where(function ($query) {
                    return $query->whereNull('deleted_at');
                }),
            ],
            'initial_balance' => ['required', 'numeric', 'min:0'],
        ];
    }
}