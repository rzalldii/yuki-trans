<?php

namespace App\Policies\Finance;

use App\Models\Finance\Wallet;
use App\Models\User\User;

class WalletPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Wallet $wallet): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Wallet $wallet): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Wallet $wallet): bool
    {
        return $user->isAdmin();
    }
}