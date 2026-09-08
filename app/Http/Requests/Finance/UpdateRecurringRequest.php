<?php

namespace App\Http\Requests\Finance;

class UpdateRecurringRequest extends StoreRecurringRequest
{
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $recurring = $this->route('finance_recurring');
        if ($recurring && $recurring->generatedTransactions()->exists()) {
            $this->merge([
                'type' => $recurring->type,
                'start_date' => $recurring->start_date ? $recurring->start_date->format('Y-m-d') : null,
            ]);
        }
    }

    public function rules(): array
    {
        $rules = parent::rules();
        $rules['is_active'] = 'required|boolean';

        return $rules;
    }
}