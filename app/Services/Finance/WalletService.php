<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Models\Audit\AuditLog;
use App\Models\Finance\Wallet;
use Illuminate\Support\Facades\DB;

class WalletService
{
    public function createWallet(array $validated): Wallet
    {
        $validated['current_balance'] = $validated['initial_balance'];
        return DB::transaction(function () use ($validated) {
            $wallet = Wallet::create($validated);
            AuditLog::record('wallet_created', null, null, [
                'name' => $wallet->name,
                'initial_balance' => $wallet->initial_balance,
            ]);
            return $wallet;
        });
    }

    public function updateWallet(Wallet $wallet, array $validated): ?Wallet
    {
        $hasTransactions = $wallet->transactions()->exists();
        $oldValues = [
            'name' => $wallet->name,
            'initial_balance' => $wallet->initial_balance,
        ];
        if (!$hasTransactions) {
            $diff = (float) $validated['initial_balance'] - (float) $wallet->initial_balance;
            $validated['current_balance'] = (float) $wallet->current_balance + $diff;
        }
        $wallet->fill($validated);
        if (!$wallet->isDirty()) {
            return null;
        }
        return DB::transaction(function () use ($wallet, $oldValues) {
            $wallet->save();
            $newValues = [
                'name' => $wallet->name,
                'initial_balance' => $wallet->initial_balance,
            ];
            AuditLog::record('wallet_updated', null, $oldValues, $newValues);
            return $wallet;
        });
    }

    public function deleteWallet(Wallet $wallet): bool
    {
        if ($wallet->transactions()->exists() || $wallet->recurrings()->exists() || $wallet->toRecurrings()->exists()) {
            return false;
        }
        $deletedInfo = [
            'name' => $wallet->name,
            'initial_balance' => $wallet->initial_balance,
        ];
        DB::transaction(function () use ($wallet, $deletedInfo) {
            AuditLog::record('wallet_deleted', null, $deletedInfo, null);
            $wallet->delete();
        });
        return true;
    }
}