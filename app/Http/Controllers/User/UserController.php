<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index()
    {
        $users = User::orderBy('username')->get();
        return view('pages.user.users', compact('users'));
    }

    public function store(Request $request): JsonResponse
    {
        $rules = [
            'username' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9_.]+$/',
                Rule::unique('users', 'username')->whereNull('deleted_at'),
            ],
            'password' => ['required', Password::min(8)->letters()->numbers()],
            'role' => ['required', Rule::in(['admin', 'user'])],
        ];
        if (!auth()->user()->isPrimary()) {
            $rules['role'] = ['required', Rule::in(['user'])];
        }
        $validated = $request->validate($rules);
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
        if (!auth()->user()->canEdit($user)) {
            return response()->json([], 403);
        }
        return response()->json($user->only(['id', 'username', 'role']));
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $currentUser = auth()->user();
        if (!$currentUser->isPrimary() && $user->isAdmin() && !$currentUser->isSelf($user)) {
            return response()->json([], 403);
        }
        $allowedRoles = $currentUser->isPrimary() ? ['admin', 'user'] : ['user'];
        if ($currentUser->isSelf($user) || $user->isPrimary()) {
            $allowedRoles[] = $user->role;
        }
        $rules = [
            'username' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9_.]+$/',
                Rule::unique('users', 'username')
                    ->whereNull('deleted_at')
                    ->ignore($user->id),
            ],
            'role' => ['required', Rule::in(array_unique($allowedRoles))],
        ];
        $validated = $request->validate($rules);
        if ($currentUser->isSelf($user) || $user->isPrimary()) {
            $validated['role'] = $user->role;
        }
        $oldValues = [
            'username' => $user->username,
            'role' => $user->role,
        ];
        if ($request->filled('password')) {
            $request->validate([
                'password' => [Password::min(8)->letters()->numbers()],
            ]);
            $validated['password'] = $request->password;
        }
        $user->fill($validated);
        if (!$user->isDirty()) {
            return response()->json([], 204);
        }
        $newValues = [
            'username' => $user->username,
            'role' => $user->role,
        ];
        if ($request->filled('password')) {
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
        if (!auth()->user()->canDelete($user)) {
            return response()->json([], 403);
        }
        $deletedInfo = [
            'username' => $user->username,
            'role' => $user->role,
        ];
        DB::transaction(function () use ($user, $deletedInfo) {
            AuditLog::record('user_deleted', $user, $deletedInfo, null);
            $user->delete();
        });
        return response()->json([], 200);
    }
}