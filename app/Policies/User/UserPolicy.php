<?php

namespace App\Policies\User;

use App\Models\User\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, User $target): bool
    {
        return $user->isAdmin() && !$target->isPrimary();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, User $target): bool
    {
        return $user->canEdit($target);
    }

    public function delete(User $user, User $target): bool
    {
        return $user->canDelete($target);
    }
}