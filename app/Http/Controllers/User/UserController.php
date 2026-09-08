<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\Audit\AuditLog;
use App\Models\User\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class UserController extends Controller
{
    public function index()
    {
        $users = User::orderBy('username')->get();
        return view('pages.user.users', compact('users'));
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        Gate::authorize('create', User::class);
        $validated = $request->validated();
        $user = DB::transaction(function () use ($validated) {
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
        $currentUser = auth()->user();
        return response()->json([
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'full_name' => $user->full_name,
                'email' => $user->email,
                'phone_number' => $user->phone_number,
                'formatted_phone_number' => $user->formatted_phone_number,
                'role' => $user->role,
                'is_primary' => $user->isPrimary(),
                'can_edit' => $currentUser->canEdit($user),
                'can_delete' => $currentUser->canDelete($user),
                'profile_url' => route('users.profile', $user),
            ]
        ], 201);
    }

    public function edit(User $user): JsonResponse
    {
        Gate::authorize('update', $user);
        return response()->json($user->only(['id', 'username', 'role']));
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        Gate::authorize('update', $user);
        $validated = $request->validated();
        $currentUser = auth()->user();
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
            return response()->json([], 204);
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
        return response()->json([
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'full_name' => $user->full_name,
                'email' => $user->email,
                'phone_number' => $user->phone_number,
                'formatted_phone_number' => $user->formatted_phone_number,
                'role' => $user->role,
                'is_primary' => $user->isPrimary(),
                'can_edit' => $currentUser->canEdit($user),
                'can_delete' => $currentUser->canDelete($user),
                'profile_url' => route('users.profile', $user),
            ]
        ], 200);
    }

    public function destroy(User $user): JsonResponse
    {
        Gate::authorize('delete', $user);
        $deletedInfo = [
            'username' => $user->username,
            'role' => $user->role,
        ];
        DB::transaction(function () use ($user, $deletedInfo) {
            AuditLog::record('user_deleted', $user, $deletedInfo, null);
            $user->delete();
        });
        return response()->json(['success' => true], 200);
    }
}