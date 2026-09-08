<?php

namespace App\Http\Requests\Finance;

use App\Models\Finance\Wallet;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWalletRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $data = [];
        if ($this->has('name') && is_string($this->name)) {
            $data['name'] = trim($this->name);
        }
        $wallet = $this->route('finance_wallet');
        if ($wallet instanceof Wallet && $wallet->transactions()->exists()) {
            $data['initial_balance'] = $wallet->initial_balance;
        }
        if (!empty($data)) {
            $this->merge($data);
        }
    }

    public function rules(): array
    {
        $wallet = $this->route('finance_wallet');
        $walletId = $wallet instanceof Wallet ? $wallet->id : null;
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('finance_wallets')->ignore($walletId)->where(function ($query) {
                    return $query->whereNull('deleted_at');
                }),
            ],
            'initial_balance' => ['required', 'numeric', 'min:0'],
        ];
    }
}