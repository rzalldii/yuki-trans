<?php

declare(strict_types=1);

namespace App\Http\Requests\Finance;

use App\Enums\CategoryType;
use App\Models\Finance\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
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
        $category = $this->route('finance_category');
        if ($category instanceof Category) {
            $hasTransactions = $category->transactions()->exists() || $category->recurrings()->exists();
            if ($hasTransactions) {
                $data['type'] = $category->type instanceof CategoryType ? $category->type->value : $category->type;
            }
        }
        if (!empty($data)) {
            $this->merge($data);
        }
    }

    public function rules(): array
    {
        $category = $this->route('finance_category');
        $categoryId = $category instanceof Category ? $category->id : null;
        $hasTransactions = $category instanceof Category && ($category->transactions()->exists() || $category->recurrings()->exists());
        $type = $hasTransactions ? ($category->type instanceof CategoryType ? $category->type->value : $category->type) : $this->input('type');
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('finance_categories')->ignore($categoryId)->where(function ($query) use ($type) {
                    return $query->where('type', $type)->whereNull('deleted_at');
                }),
            ],
            'type' => ['required', Rule::enum(CategoryType::class)],
            'amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}