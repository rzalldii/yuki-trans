<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AuditLog extends Model
{
    const UPDATED_AT = null;
    public const CACHE_KEY_ACTIONS = 'audit_log_actions';
    public const CACHE_KEY_CAUSERS = 'audit_log_causers';
    public const CACHE_KEY_SUBJECTS = 'audit_log_subjects';
    public const CACHE_KEY_TOTAL_COUNT = 'audit_log_total_count';
    public const CACHE_TTL = 3600;

    public const ACTION_BADGES = [
        'login' => 'success',
        'logout' => 'secondary',
        'login_failed' => 'warning',
        'login_blocked' => 'danger',
        'access_denied' => 'danger',
        'user_created' => 'success',
        'user_updated' => 'info',
        'user_deleted' => 'danger',
        'profile_updated' => 'info',
        'password_updated' => 'warning',
    ];

    protected $fillable = [
        'causer_id',
        'causer_username',
        'subject_id',
        'subject_username',
        'action',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    protected static function booted(): void
    {
        static::created(function () {
            Cache::forget(self::CACHE_KEY_ACTIONS);
            Cache::forget(self::CACHE_KEY_CAUSERS);
            Cache::forget(self::CACHE_KEY_SUBJECTS);
            Cache::forget(self::CACHE_KEY_TOTAL_COUNT);
        });
    }

    public function causer()
    {
        return $this->belongsTo(User::class, 'causer_id');
    }

    public function subject()
    {
        return $this->belongsTo(User::class, 'subject_id');
    }

    public function getActionLabelAttribute(): string
    {
        return strtoupper(str_replace('_', ' ', $this->action));
    }

    public function getActionBadgeClassAttribute(): string
    {
        $tone = self::ACTION_BADGES[$this->action] ?? 'primary';
        return "bg-label-{$tone}";
    }

    public function scopeForListing($query)
    {
        return $query->select([
            'id',
            'causer_username',
            'subject_username',
            'action',
            'ip_address',
            'created_at',
        ])->selectRaw('(old_values IS NOT NULL OR new_values IS NOT NULL) as has_detail');
    }

    public static function record(string $action, ?User $subject = null, ?array $oldValues = null, ?array $newValues = null): self
    {
        $causer = auth()->user();
        [$diffOld, $diffNew] = self::diff($oldValues, $newValues);
        return self::create([
            'causer_id' => $causer?->id,
            'causer_username' => $causer?->username,
            'subject_id' => $subject?->id,
            'subject_username' => $subject?->username,
            'action' => $action,
            'old_values' => $diffOld,
            'new_values' => $diffNew,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    protected static function diff(?array $old, ?array $new): array
    {
        if ($old === null || $new === null) {
            return [$old, $new];
        }
        $changedKeys = array_keys(
            array_filter($new, fn($value, $key) => !array_key_exists($key, $old) || $old[$key] !== $value, ARRAY_FILTER_USE_BOTH)
        );
        if (empty($changedKeys)) {
            return [null, null];
        }
        return [
            array_intersect_key($old, array_flip($changedKeys)),
            array_intersect_key($new, array_flip($changedKeys)),
        ];
    }
}