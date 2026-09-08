<?php

namespace App\Http\Requests\User;

use App\Models\User\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $targetUser = $this->route('user');
        if (!$targetUser instanceof User) {
            return false;
        }

        return $this->user()?->can('update', $targetUser) ?? false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('username') && is_string($this->username)) {
            $this->merge([
                'username' => strtolower(trim($this->username)),
            ]);
        }
    }

    public function rules(): array
    {
        $targetUser = $this->route('user');
        $currentUser = $this->user();
        $allowedRoles = $currentUser?->isPrimary() ? ['admin', 'user'] : ['user'];
        if ($currentUser && $targetUser && ($currentUser->isSelf($targetUser) || $targetUser->isPrimary())) {
            $allowedRoles[] = $targetUser->role;
        }
        return [
            'username' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9_.]+$/',
                Rule::unique('users', 'username')
                    ->whereNull('deleted_at')
                    ->ignore($targetUser?->id),
            ],
            'role' => ['required', Rule::in(array_unique($allowedRoles))],
            'password' => ['nullable', Password::min(8)->letters()->numbers()],
        ];
    }
}