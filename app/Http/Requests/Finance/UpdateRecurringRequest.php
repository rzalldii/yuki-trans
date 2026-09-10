<?php

declare(strict_types=1);

namespace App\Http\Requests\Finance;

use App\Enums\RecurringType;

class UpdateRecurringRequest extends StoreRecurringRequest
{
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();
        $recurring = $this->route('finance_recurring');
        if ($recurring && $recurring->generatedTransactions()->exists()) {
            $typeVal = $recurring->type instanceof RecurringType ? $recurring->type->value : $recurring->type;
            $this->merge([
                'type' => $typeVal,
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