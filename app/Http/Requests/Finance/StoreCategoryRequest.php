<?php

declare(strict_types=1);

namespace App\Http\Requests\Finance;

use App\Enums\CategoryType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
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
        $type = $this->input('type');
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('finance_categories')->where(function ($query) use ($type) {
                    return $query->where('type', $type)->whereNull('deleted_at');
                }),
            ],
            'type' => ['required', Rule::enum(CategoryType::class)],
            'amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}