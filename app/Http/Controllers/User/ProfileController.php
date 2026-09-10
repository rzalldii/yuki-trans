<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\UpdatePasswordRequest;
use App\Http\Requests\User\UpdateProfileRequest;
use App\Models\Audit\AuditLog;
use App\Models\User\User;
use App\Services\User\ProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
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

    public function update(UpdateProfileRequest $request, ProfileService $service): JsonResponse
    {
        $user = auth()->user();
        $updatedUser = $service->updateProfile($user, $request->validated());
        if (!$updatedUser) {
            return response()->json([], 204);
        }
        return response()->json([
            'success' => true,
            'user' => [
                'username' => $updatedUser->username,
                'full_name' => $updatedUser->full_name,
                'email' => $updatedUser->email,
                'phone_number' => $updatedUser->phone_number,
                'formatted_phone_number' => $updatedUser->formatted_phone_number,
                'address' => $updatedUser->address,
            ]
        ], 200);
    }

    public function updatePassword(UpdatePasswordRequest $request, ProfileService $service): JsonResponse
    {
        $user = auth()->user();
        $validated = $request->validated();
        if (!$service->updatePassword($user, $validated['current_password'], $validated['password'])) {
            return response()->json(['errors' => ['current_password' => true]], 422);
        }
        return response()->json(['success' => true], 200);
    }
}