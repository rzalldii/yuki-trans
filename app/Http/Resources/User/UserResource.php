<?php

declare(strict_types=1);

namespace App\Http\Resources\User;

use App\Enums\UserRole;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $roleVal = $this->role instanceof UserRole ? $this->role->value : (string) $this->role;
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'role' => $roleVal,
            'role_label' => $this->role instanceof UserRole ? $this->role->label() : ($roleVal === 'admin' ? 'Administrator' : 'Standard User'),
            'is_primary' => (bool) $this->is_primary,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}