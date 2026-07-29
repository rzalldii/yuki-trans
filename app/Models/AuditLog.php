<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    const UPDATED_AT = null;

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

    public function causer()
    {
        return $this->belongsTo(User::class, 'causer_id');
    }

    public function subject()
    {
        return $this->belongsTo(User::class, 'subject_id');
    }

    public static function record(string $action, ?int $subjectId = null, ?array $oldValues = null, ?array $newValues = null): self
    {
        $causer = auth()->user();
        $subject = $subjectId ? User::find($subjectId) : null;
        [$diffOld, $diffNew] = self::diff($oldValues, $newValues);
        return self::create([
            'causer_id' => $causer?->id,
            'causer_username' => $causer?->username,
            'subject_id' => $subjectId,
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