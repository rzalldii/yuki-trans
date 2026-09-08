<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\UpdatePasswordRequest;
use App\Http\Requests\User\UpdateProfileRequest;
use App\Models\Audit\AuditLog;
use App\Models\User\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(): View
    {
        return $this->buildProfileView(auth()->user(), false);
    }

    public function showUser(User $user): View
    {
        if ($user->isPrimary()) {
            abort(403);
        }
        return $this->buildProfileView($user, true);
    }

    private function buildProfileView(User $profileUser, bool $isAdminView): View
    {
        $userId = $profileUser->id;
        $activities = AuditLog::query()
            ->forListing()
            ->where('causer_id', $userId)
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get()
            ->map(function ($log) {
                return [
                    'log_id' => $log->id,
                    'action_label' => $log->action_label,
                    'action_badge' => $log->action_badge_class,
                    'date' => $log->created_at->format('d M Y, H:i'),
                    'ip_address' => $log->ip_address,
                    'has_detail' => true,
                    'has_diff' => (bool) $log->has_detail,
                ];
            })
            ->values();
        $totalActivities = Cache::remember(
            "user_{$userId}_activity_count",
            300,
            fn() => AuditLog::where('causer_id', $userId)->count()
        );
        return view('pages.user.profile', compact('activities', 'totalActivities', 'profileUser', 'isAdminView'));
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = auth()->user();
        $validated = $request->validated();
        $oldValues = $user->only(['username', 'full_name', 'email', 'phone_number', 'address']);
        $user->fill($validated);
        if (!$user->isDirty()) {
            return response()->json([], 204);
        }
        $changedFields = array_keys($user->getDirty());
        $filteredOldValues = collect($oldValues)->only($changedFields)->toArray();
        $filteredNewValues = collect($validated)->only($changedFields)->toArray();
        DB::transaction(function () use ($user, $filteredOldValues, $filteredNewValues) {
            $user->save();
            AuditLog::record('profile_updated', $user, $filteredOldValues, $filteredNewValues);
        });
        return response()->json([
            'user' => [
                'username' => $user->username,
                'full_name' => $user->full_name,
                'email' => $user->email,
                'phone_number' => $user->phone_number,
                'formatted_phone_number' => $user->formatted_phone_number,
                'address' => $user->address,
            ]
        ], 200);
    }

    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $user = auth()->user();
        $validated = $request->validated();
        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json(['errors' => ['current_password' => true]], 422);
        }
        DB::transaction(function () use ($user, $validated) {
            $user->update(['password' => $validated['password']]);
            Auth::logoutOtherDevices($validated['password']);
            $user->forceFill([
                'remember_token' => Str::random(60),
            ])->save();
            AuditLog::record('password_updated', $user);
        });
        return response()->json(['success' => true], 200);
    }
}