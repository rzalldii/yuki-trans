<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Enums\Finance\RecurringType;
use App\Enums\Finance\TransactionType;
use App\Exceptions\Finance\InsufficientBalanceException;
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
        $generated = 0;
        $todayDate = now()->toDateString();
        Recurring::active()
            ->dueOn($todayDate)
            ->with(['wallet', 'toWallet', 'category', 'tags'])
            ->chunkById(50, function ($dueRecurrings) use ($userId, $todayDate, &$generated) {
                foreach ($dueRecurrings as $recurring) {
                    try {
                        $maxIterations = 366;
                        $iterations = 0;
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
            });
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
        return DB::transaction(function () use ($recurring, $userId) {
            $lockedRecurring = Recurring::where('id', $recurring->id)->lockForUpdate()->first();
            if (!$lockedRecurring || !$lockedRecurring->is_active || !$lockedRecurring->next_due_date) {
                return null;
            }
            $userId = $userId ?? auth()->id() ?? User::where('role', 'admin')->value('id') ?? 1;
            $txDate = $lockedRecurring->next_due_date;
            $isTransfer = $lockedRecurring->type === RecurringType::Transfer || $lockedRecurring->type === 'transfer';
            if ($isTransfer) {
                if (!$lockedRecurring->wallet_id || !$lockedRecurring->to_wallet_id) {
                    return null;
                }
                $fromWallet = Wallet::where('id', $lockedRecurring->wallet_id)->lockForUpdate()->first();
                $toWallet = Wallet::where('id', $lockedRecurring->to_wallet_id)->lockForUpdate()->first();
                if (!$fromWallet || !$toWallet) {
                    return null;
                }
                if ((float) $fromWallet->current_balance < (float) $lockedRecurring->amount) {
                    throw new InsufficientBalanceException('Insufficient wallet balance for recurring transfer');
                }
                $description = !empty($lockedRecurring->description) ? $lockedRecurring->description . ' (Auto)' : 'Transfer (Auto)';
                $out = Transaction::create([
                    'user_id' => $userId,
                    'wallet_id' => $fromWallet->id,
                    'type' => TransactionType::TransferOut->value,
                    'amount' => $lockedRecurring->amount,
                    'description' => $description,
                    'transaction_date' => $txDate,
                    'recurring_id' => $lockedRecurring->id,
                ]);
                $in = Transaction::create([
                    'user_id' => $userId,
                    'wallet_id' => $toWallet->id,
                    'type' => TransactionType::TransferIn->value,
                    'amount' => $lockedRecurring->amount,
                    'description' => $description,
                    'transaction_date' => $txDate,
                    'recurring_id' => $lockedRecurring->id,
                ]);
                $out->update(['transfer_pair_id' => $in->id]);
                $in->update(['transfer_pair_id' => $out->id]);
                if ($lockedRecurring->relationLoaded('tags') ? $lockedRecurring->tags->isNotEmpty() : $lockedRecurring->tags()->exists()) {
                    $tagIds = $lockedRecurring->tags->pluck('id');
                    $out->tags()->sync($tagIds);
                    $in->tags()->sync($tagIds);
                }
                $fromWallet->adjustBalance(TransactionType::TransferOut, (float) $lockedRecurring->amount);
                $toWallet->adjustBalance(TransactionType::TransferIn, (float) $lockedRecurring->amount);
                $transaction = $out;
            } else {
                $txType = TransactionType::from($lockedRecurring->type instanceof RecurringType ? $lockedRecurring->type->value : (string) $lockedRecurring->type);
                $wallet = Wallet::where('id', $lockedRecurring->wallet_id)->lockForUpdate()->first();
                if (!$wallet) {
                    return null;
                }
                if ($txType === TransactionType::Expense && (float) $wallet->current_balance < (float) $lockedRecurring->amount) {
                    throw new InsufficientBalanceException('Insufficient wallet balance for recurring expense');
                }
                $description = !empty($lockedRecurring->description) ? $lockedRecurring->description . ' (Auto)' : ($lockedRecurring->category->name ?? 'Recurring') . ' (Auto)';
                $transaction = Transaction::create([
                    'user_id' => $userId,
                    'wallet_id' => $lockedRecurring->wallet_id,
                    'category_id' => $lockedRecurring->category_id,
                    'type' => $txType->value,
                    'amount' => $lockedRecurring->amount,
                    'description' => $description,
                    'transaction_date' => $txDate,
                    'recurring_id' => $lockedRecurring->id,
                ]);
                if ($lockedRecurring->relationLoaded('tags') ? $lockedRecurring->tags->isNotEmpty() : $lockedRecurring->tags()->exists()) {
                    $transaction->tags()->sync($lockedRecurring->tags->pluck('id'));
                }
                $wallet->adjustBalance($txType, (float) $lockedRecurring->amount);
            }
            $dateForCalc = $txDate instanceof Carbon ? $txDate : Carbon::parse($txDate);
            $nextDue = $lockedRecurring->calculateNextDueDate($dateForCalc);
            $lockedRecurring->update([
                'last_generated_at' => $txDate,
                'next_due_date' => $nextDue,
                'is_active' => $nextDue !== null,
            ]);
            $recurring->setRawAttributes($lockedRecurring->getAttributes(), true);
            return $transaction;
        });
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