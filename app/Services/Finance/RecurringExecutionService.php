<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Enums\RecurringType;
use App\Enums\TransactionType;
use App\Models\Audit\AuditLog;
use App\Models\Finance\Recurring;
use App\Models\Finance\Transaction;
use App\Models\Finance\Wallet;
use App\Models\User\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

class RecurringExecutionService
{
    public function processDueRecurrings(?int $userId = null): int
    {
        $dueRecurrings = Recurring::active()
            ->dueOn(now()->toDateString())
            ->with(['wallet', 'toWallet', 'category', 'tags'])
            ->get();
        $generated = 0;
        foreach ($dueRecurrings as $recurring) {
            try {
                $maxIterations = 366;
                $iterations = 0;
                $todayDate = now()->toDateString();
                while (
                    $recurring->is_active &&
                    $recurring->next_due_date &&
                    $recurring->next_due_date->lte($todayDate) &&
                    $iterations < $maxIterations
                ) {
                    $prevDueDate = $recurring->next_due_date->toDateString();
                    $tx = $this->executeRecurring($recurring, $userId);
                    if ($tx) {
                        $generated++;
                    } else {
                        break;
                    }
                    $recurring->refresh();
                    if ($recurring->next_due_date && $recurring->next_due_date->toDateString() === $prevDueDate) {
                        break;
                    }
                    $iterations++;
                }
            } catch (Throwable $e) {
                AuditLog::record('recurring_failed', null, null, [
                    'recurring_id' => $recurring->id,
                    'type' => $recurring->type instanceof RecurringType ? $recurring->type->value : $recurring->type,
                    'wallet' => $recurring->wallet->name ?? 'Unknown',
                    'to_wallet' => $recurring->toWallet->name ?? null,
                    'category' => $recurring->category->name ?? null,
                    'amount' => $recurring->amount,
                    'reason' => $e->getMessage(),
                ], $this->systemContext());
            }
        }
        if ($generated > 0) {
            AuditLog::record('recurring_generated', null, null, [
                'count' => $generated,
                'date' => now()->toDateString(),
            ], $this->systemContext());
        }
        return $generated;
    }

    public function executeRecurring(Recurring $recurring, ?int $userId = null): ?Transaction
    {
        $userId = $userId ?? auth()->id() ?? User::where('role', 'admin')->value('id') ?? 1;
        $txDate = $recurring->next_due_date ?? now()->toDateString();
        $isTransfer = $recurring->type === RecurringType::Transfer || $recurring->type === 'transfer';
        if ($isTransfer) {
            if (!$recurring->wallet_id || !$recurring->to_wallet_id) {
                return null;
            }
            $description = !empty($recurring->description) ? $recurring->description . ' (Auto)' : 'Transfer (Auto)';
            $transaction = DB::transaction(function () use ($recurring, $userId, $txDate, $description) {
                $fromWallet = Wallet::where('id', $recurring->wallet_id)->lockForUpdate()->first();
                $toWallet = Wallet::where('id', $recurring->to_wallet_id)->lockForUpdate()->first();
                if (!$fromWallet || !$toWallet) {
                    return null;
                }
                $out = Transaction::create([
                    'user_id' => $userId,
                    'wallet_id' => $fromWallet->id,
                    'type' => TransactionType::TransferOut->value,
                    'amount' => $recurring->amount,
                    'description' => $description,
                    'transaction_date' => $txDate,
                    'recurring_id' => $recurring->id,
                ]);
                $in = Transaction::create([
                    'user_id' => $userId,
                    'wallet_id' => $toWallet->id,
                    'type' => TransactionType::TransferIn->value,
                    'amount' => $recurring->amount,
                    'description' => $description,
                    'transaction_date' => $txDate,
                    'recurring_id' => $recurring->id,
                ]);
                $out->update(['transfer_pair_id' => $in->id]);
                $in->update(['transfer_pair_id' => $out->id]);
                if ($recurring->relationLoaded('tags') ? $recurring->tags->isNotEmpty() : $recurring->tags()->exists()) {
                    $tagIds = $recurring->tags->pluck('id');
                    $out->tags()->sync($tagIds);
                    $in->tags()->sync($tagIds);
                }
                $fromWallet->adjustBalance(TransactionType::TransferOut, (float) $recurring->amount);
                $toWallet->adjustBalance(TransactionType::TransferIn, (float) $recurring->amount);
                return $out;
            });
        } else {
            $description = !empty($recurring->description) ? $recurring->description . ' (Auto)' : ($recurring->category->name ?? 'Recurring') . ' (Auto)';
            $txType = TransactionType::from($recurring->type instanceof RecurringType ? $recurring->type->value : (string) $recurring->type);
            $transactionData = [
                'user_id' => $userId,
                'wallet_id' => $recurring->wallet_id,
                'category_id' => $recurring->category_id,
                'type' => $txType->value,
                'amount' => $recurring->amount,
                'description' => $description,
                'transaction_date' => $txDate,
                'recurring_id' => $recurring->id,
            ];
            $transaction = DB::transaction(function () use ($recurring, $transactionData, $txType) {
                $wallet = Wallet::where('id', $recurring->wallet_id)->lockForUpdate()->first();
                $transaction = Transaction::create($transactionData);
                if ($recurring->relationLoaded('tags') ? $recurring->tags->isNotEmpty() : $recurring->tags()->exists()) {
                    $transaction->tags()->sync($recurring->tags->pluck('id'));
                }
                if ($wallet) {
                    $wallet->adjustBalance($txType, (float) $recurring->amount);
                }
                return $transaction;
            });
        }
        $nextDue = $recurring->calculateNextDueDate($txDate instanceof Carbon ? $txDate : Carbon::parse($txDate));
        $recurring->update([
            'last_generated_at' => $txDate,
            'next_due_date' => $nextDue,
            'is_active' => $nextDue !== null,
        ]);
        return $transaction;
    }

    public function deleteGeneratedTransactions(Recurring $recurring, string $auditNote): void
    {
        $transactions = $recurring->generatedTransactions()
            ->with(['transferPair.wallet', 'wallet', 'category'])
            ->get();
        if ($transactions->isEmpty()) {
            return;
        }
        $walletIds = $transactions->pluck('wallet_id')
            ->merge($transactions->pluck('transferPair.wallet_id'))
            ->filter()
            ->unique()
            ->values()
            ->all();
        $lockedWallets = Wallet::whereIn('id', $walletIds)
            ->lockForUpdate()
            ->get()
            ->keyBy('id');
        $processedPairs = [];
        foreach ($transactions as $tx) {
            if ($tx->isTransfer() && $tx->transferPair) {
                if (isset($processedPairs[$tx->id])) {
                    continue;
                }
                $pair = $tx->transferPair;
                $processedPairs[$tx->id] = true;
                $processedPairs[$pair->id] = true;
                $isTransferOut = $tx->type === TransactionType::TransferOut || $tx->type === 'transfer_out';
                $outTx = $isTransferOut ? $tx : $pair;
                $isTransferIn = $tx->type === TransactionType::TransferIn || $tx->type === 'transfer_in';
                $inTx = $isTransferIn ? $tx : $pair;
                $outWallet = $lockedWallets->get($outTx->wallet_id) ?? $outTx->wallet;
                $inWallet = $lockedWallets->get($inTx->wallet_id) ?? $inTx->wallet;
                $outWallet?->revertBalance(TransactionType::TransferOut, (float) $outTx->amount);
                $inWallet?->revertBalance(TransactionType::TransferIn, (float) $inTx->amount);
                AuditLog::record('transfer_deleted', null, [
                    'from_wallet' => $outWallet->name ?? 'Unknown',
                    'to_wallet' => $inWallet->name ?? 'Unknown',
                    'amount' => $outTx->amount,
                    'note' => $auditNote,
                ], null, $this->systemContext());
                $pair->update(['transfer_pair_id' => null]);
                $tx->update(['transfer_pair_id' => null]);
                $pair->delete();
                $tx->delete();
            } else {
                $w = $lockedWallets->get($tx->wallet_id) ?? $tx->wallet;
                if ($w) {
                    $w->revertBalance($tx->type, (float) $tx->amount);
                }
                AuditLog::record('transaction_deleted', null, [
                    'wallet' => $w->name ?? 'Unknown',
                    'category' => $tx->category->name ?? 'Unknown',
                    'type' => $tx->type instanceof TransactionType ? $tx->type->value : $tx->type,
                    'amount' => $tx->amount,
                    'note' => $auditNote,
                ], null, $this->systemContext());
                $tx->delete();
            }
        }
    }

    private function systemContext(): array
    {
        if (!app()->runningInConsole()) {
            return [];
        }
        return [
            'ip_address' => 'system',
            'user_agent' => 'artisan/scheduler',
            'url' => 'console',
            'method' => 'CLI',
        ];
    }
}