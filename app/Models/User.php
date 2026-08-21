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
            'remember_token_created_at' => 'datetime',
        ];
    }

    public function setUsernameAttribute($value)
    {
        $this->attributes['username'] = strtolower($value);
    }

    public function setPhoneNumberAttribute($value): void
    {
        if (empty($value)) {
            $this->attributes['phone_number'] = null;
            return;
        }
        $phone = preg_replace('/[^0-9]/', '', (string) $value);
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }
        $this->attributes['phone_number'] = $phone ?: null;
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

    public function auditLogsAsCauser()
    {
        return $this->hasMany(AuditLog::class, 'causer_id');
    }

    public function auditLogsAsSubject()
    {
        return $this->hasMany(AuditLog::class, 'subject_id');
    }

    public function getFormattedPhoneNumberAttribute(): ?string
    {
        $phone = $this->phone_number;
        if (!$phone) {
            return null;
        }
        if (str_starts_with($phone, '62')) {
            $number = substr($phone, 2);
            if (strlen($number) >= 8) {
                $p1 = substr($number, 0, 3);
                $p2 = substr($number, 3, 4);
                $p3 = substr($number, 7);
                return trim("+62 {$p1}-{$p2}-{$p3}", '-');
            }
            return "+62 {$number}";
        }
        return $phone;
    }
}