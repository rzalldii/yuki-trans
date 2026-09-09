<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRecurringRequest extends FormRequest
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
        $type = $this->input('type');
        $rules = [
            'type' => 'required|in:income,expense,transfer',
            'wallet_id' => ['required', Rule::exists('finance_wallets', 'id')->whereNull('deleted_at')],
            'amount' => ['required', 'numeric', 'min:1'],
            'description' => 'nullable|string|max:1000',
            'frequency' => 'required|in:daily,weekly,monthly,yearly',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'tags' => 'nullable|array|max:10',
            'tags.*' => 'string|max:50',
        ];
        if ($type === 'transfer') {
            $rules['to_wallet_id'] = ['required', 'different:wallet_id', Rule::exists('finance_wallets', 'id')->whereNull('deleted_at')];
            $rules['category_id'] = 'nullable';
        } else {
            $rules['category_id'] = [
                'required',
                Rule::exists('finance_categories', 'id')->where(fn($q) => $q->where('type', $type)->whereNull('deleted_at')),
            ];
            $rules['to_wallet_id'] = 'nullable';
        }
        return $rules;
    }
}