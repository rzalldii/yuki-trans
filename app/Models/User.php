<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'username',
        'password',
        'role',
        'full_name',
        'email',
        'phone_number',
        'address',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_primary' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isPrimary(): bool
    {
        return $this->is_primary === true;
    }

    public function isSelf(User $target): bool
    {
        return $this->id === $target->id;
    }

    public function canEdit(User $target): bool
    {
        if ($this->isSelf($target)) {
            return false;
        }
        return $this->isPrimary() || !$target->isAdmin();
    }

    public function canDelete(User $target): bool
    {
        if ($this->isSelf($target) || $target->isPrimary()) {
            return false;
        }
        return $this->isPrimary() || !$target->isAdmin();
    }
}
