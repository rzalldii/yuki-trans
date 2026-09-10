<?php

declare(strict_types=1);

namespace App\Services\User;

use App\Models\Audit\AuditLog;
use App\Models\User\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ProfileService
{
    public function updateProfile(User $user, array $validated): ?User
    {
        $oldValues = $user->only(['username', 'full_name', 'email', 'phone_number', 'address']);
        $user->fill($validated);
        if (!$user->isDirty()) {
            return null;
        }
        $changedFields = array_keys($user->getDirty());
        $filteredOldValues = collect($oldValues)->only($changedFields)->toArray();
        $filteredNewValues = collect($validated)->only($changedFields)->toArray();
        DB::transaction(function () use ($user, $filteredOldValues, $filteredNewValues) {
            $user->save();
            AuditLog::record('profile_updated', $user, $filteredOldValues, $filteredNewValues);
        });
        return $user;
    }

    public function updatePassword(User $user, string $currentPassword, string $newPassword): bool
    {
        if (!Hash::check($currentPassword, $user->password)) {
            return false;
        }
        DB::transaction(function () use ($user, $newPassword) {
            $user->update(['password' => $newPassword]);
            Auth::logoutOtherDevices($newPassword);
            $user->forceFill([
                'remember_token' => Str::random(60),
            ])->save();
            AuditLog::record('password_updated', $user);
        });
        return true;
    }
}