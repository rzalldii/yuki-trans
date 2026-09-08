<?php

namespace App\Policies\Finance;

use App\Models\Finance\Recurring;
use App\Models\User\User;

class RecurringPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Recurring $recurring): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Recurring $recurring): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Recurring $recurring): bool
    {
        return $user->isAdmin();
    }
}