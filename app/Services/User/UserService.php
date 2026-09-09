<?php

namespace App\Services\User;

use App\Models\Audit\AuditLog;
use App\Models\User\User;
use Illuminate\Support\Facades\DB;

class UserService
{
    public function createUser(array $validated): User
    {
        return DB::transaction(function () use ($validated) {
            $user = User::create([
                'username' => $validated['username'],
                'password' => $validated['password'],
                'role' => $validated['role'],
            ]);
            AuditLog::record('user_created', $user, null, [
                'username' => $user->username,
                'role' => $user->role,
            ]);
            return $user;
        });
    }

    public function updateUser(User $user, array $validated, User $currentUser): ?User
    {
        if ($currentUser->isSelf($user) || $user->isPrimary()) {
            $validated['role'] = $user->role;
        }
        $oldValues = [
            'username' => $user->username,
            'role' => $user->role,
        ];
        $hasNewPassword = !empty($validated['password']);
        if (!$hasNewPassword) {
            unset($validated['password']);
        }
        $user->fill($validated);
        if (!$user->isDirty()) {
            return null;
        }
        $newValues = [
            'username' => $user->username,
            'role' => $user->role,
        ];
        if ($hasNewPassword) {
            $newValues['password'] = 'changed';
        }
        $subject = $currentUser->isSelf($user) ? null : $user;
        DB::transaction(function () use ($user, $subject, $oldValues, $newValues) {
            $user->save();
            AuditLog::record('user_updated', $subject, $oldValues, $newValues);
        });
        return $user;
    }

    public function deleteUser(User $user): void
    {
        $deletedInfo = [
            'username' => $user->username,
            'role' => $user->role,
        ];
        DB::transaction(function () use ($user, $deletedInfo) {
            AuditLog::record('user_deleted', $user, $deletedInfo, null);
            $user->delete();
        });
    }
}