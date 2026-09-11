<?php

declare(strict_types=1);

namespace App\Http\Requests\Finance;

use App\Models\Finance\Tag;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTagRequest extends FormRequest
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
        $tag = $this->route('finance_tag');
        $tagId = $tag instanceof Tag ? $tag->id : null;
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('finance_tags')->ignore($tagId)->where(function ($query) {
                    return $query->whereNull('deleted_at');
                }),
            ],
            'color' => ['nullable', 'string', 'regex:/^#[a-fA-F0-9]{6}$/'],
        ];
    }
}