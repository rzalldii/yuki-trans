<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
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
                    'has_detail' => (bool) $log->has_detail,
                ];
            })
            ->values();
        $totalActivities = AuditLog::where('causer_id', $userId)->count();
        return view('pages.user.profile', compact('activities', 'totalActivities', 'profileUser', 'isAdminView'));
    }

    public function update(Request $request): JsonResponse
    {
        $user = auth()->user();
        if ($request->filled('phone_number')) {
            $phone = preg_replace('/[^0-9]/', '', $request->input('phone_number'));
            if (str_starts_with($phone, '0')) {
                $phone = '62' . substr($phone, 1);
            }
            $request->merge(['phone_number' => $phone]);
        }
        $validated = $request->validate([
            'username' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9_.]+$/',
                Rule::unique('users', 'username')
                    ->whereNull('deleted_at')
                    ->ignore($user->id),
            ],
            'full_name' => 'nullable|string|max:255',
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')
                    ->whereNull('deleted_at')
                    ->ignore($user->id),
            ],
            'phone_number' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('users', 'phone_number')
                    ->whereNull('deleted_at')
                    ->ignore($user->id),
            ],
            'address' => 'nullable|string|max:500',
        ]);
        $oldValues = $user->only(['username', 'full_name', 'email', 'phone_number', 'address']);
        $user->fill($validated);
        if (!$user->isDirty()) {
            return response()->json([], 204);
        }
        $changedFields = array_keys($user->getDirty());
        $filteredOldValues = collect($oldValues)->only($changedFields)->toArray();
        $filteredNewValues = collect($validated)->only($changedFields)->toArray();
        $user->save();
        AuditLog::record('profile_updated', null, $filteredOldValues, $filteredNewValues);
        return response()->json([], 200);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $user = auth()->user();
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);
        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json(['errors' => ['current_password' => true]], 422);
        }
        $user->update(['password' => $validated['password']]);
        AuditLog::record('password_updated', null);
        return response()->json([], 200);
    }
}