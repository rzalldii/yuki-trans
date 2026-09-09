<?php

namespace App\Policies\Finance;

use App\Models\Finance\Transaction;
use App\Models\User\User;

class TransactionPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function view(User $user, Transaction $transaction): bool
    {
        return $user->isAdmin() || $transaction->user_id === $user->id;
    }

    public function update(User $user, Transaction $transaction): bool
    {
        return $user->isAdmin() || $transaction->user_id === $user->id;
    }

    public function delete(User $user, Transaction $transaction): bool
    {
        return $user->isAdmin() || $transaction->user_id === $user->id;
    }
}