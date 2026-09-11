<?php

declare(strict_types=1);

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $data = [];
        if ($this->has('name') && is_string($this->name)) {
            $data['name'] = trim($this->name);
        }
        if ($this->has('color') && is_string($this->color)) {
            $data['color'] = trim($this->color);
        }
        if (!empty($data)) {
            $this->merge($data);
        }
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('finance_tags')->where(function ($query) {
                    return $query->whereNull('deleted_at');
                }),
            ],
            'color' => ['nullable', 'string', 'regex:/^#[a-fA-F0-9]{6}$/'],
        ];
    }
}