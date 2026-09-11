<?php

declare(strict_types=1);

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\User\UserResource;
use App\Models\User\User;
use App\Services\User\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class UserController extends Controller
{
    public function index()
    {
        $users = User::orderBy('username')->get();
        return view('pages.user.users', compact('users'));
    }

    public function store(StoreUserRequest $request, UserService $service): JsonResponse
    {
        Gate::authorize('create', User::class);
        $user = $service->createUser($request->validated());
        return response()->json([
            'success' => true,
            'user' => new UserResource($user),
        ], 201);
    }

    public function edit(User $user): JsonResponse
    {
        Gate::authorize('update', $user);
        return response()->json([
            'id' => $user->id,
            'username' => $user->username,
            'role' => $user->roleValue(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user, UserService $service): JsonResponse
    {
        Gate::authorize('update', $user);
        $updatedUser = $service->updateUser($user, $request->validated(), auth()->user());
        if (!$updatedUser) {
            return response()->json([], 204);
        }
        return response()->json([
            'success' => true,
            'user' => new UserResource($updatedUser),
        ], 200);
    }

    public function destroy(User $user, UserService $service): JsonResponse
    {
        Gate::authorize('delete', $user);
        $service->deleteUser($user);
        return response()->json(['success' => true], 200);
    }
}