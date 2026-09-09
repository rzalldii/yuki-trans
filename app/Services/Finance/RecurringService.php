<?php

namespace App\Services\Finance;

use App\Models\Audit\AuditLog;
use App\Models\Finance\Category;
use App\Models\Finance\Recurring;
use App\Models\Finance\Tag;
use App\Models\Finance\Transaction;
use App\Models\Finance\Wallet;
use App\Models\User\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

class RecurringService
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
                    'type' => $recurring->type,
                    'wallet' => $recurring->wallet->name ?? 'Unknown',
                    'to_wallet' => $recurring->toWallet->name ?? null,
                    'category' => $recurring->category->name ?? null,
                    'amount' => $recurring->amount,
                    'reason' => $e->getMessage(),
                ]);
            }
        }
        if ($generated > 0) {
            AuditLog::record('recurring_generated', null, null, [
                'count' => $generated,
                'date' => now()->toDateString(),
            ]);
        }
        return $generated;
    }

    public function executeRecurring(Recurring $recurring, ?int $userId = null): ?Transaction
    {
        $userId = $userId ?? auth()->id() ?? User::where('role', 'admin')->value('id') ?? 1;
        $txDate = $recurring->next_due_date ?? now()->toDateString();
        if ($recurring->type === 'transfer') {
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
                    'type' => 'transfer_out',
                    'amount' => $recurring->amount,
                    'description' => $description,
                    'transaction_date' => $txDate,
                    'recurring_id' => $recurring->id,
                ]);
                $in = Transaction::create([
                    'user_id' => $userId,
                    'wallet_id' => $toWallet->id,
                    'type' => 'transfer_in',
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
                $fromWallet->adjustBalance('transfer_out', (float) $recurring->amount);
                $toWallet->adjustBalance('transfer_in', (float) $recurring->amount);
                return $out;
            });
        } else {
            $description = !empty($recurring->description) ? $recurring->description . ' (Auto)' : ($recurring->category->name ?? 'Recurring') . ' (Auto)';
            $transactionData = [
                'user_id' => $userId,
                'wallet_id' => $recurring->wallet_id,
                'category_id' => $recurring->category_id,
                'type' => $recurring->type,
                'amount' => $recurring->amount,
                'description' => $description,
                'transaction_date' => $txDate,
                'recurring_id' => $recurring->id,
            ];
            $transaction = DB::transaction(function () use ($recurring, $transactionData) {
                $wallet = Wallet::where('id', $recurring->wallet_id)->lockForUpdate()->first();
                $transaction = Transaction::create($transactionData);
                if ($recurring->relationLoaded('tags') ? $recurring->tags->isNotEmpty() : $recurring->tags()->exists()) {
                    $transaction->tags()->sync($recurring->tags->pluck('id'));
                }
                if ($wallet) {
                    $wallet->adjustBalance($recurring->type, (float) $recurring->amount);
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

    public function createRecurring(array $validated): Recurring
    {
        $type = $validated['type'];
        $wallet = Wallet::findOrFail($validated['wallet_id']);
        $toWallet = null;
        $category = null;
        if ($type === 'transfer') {
            $toWallet = Wallet::findOrFail($validated['to_wallet_id']);
            $validated['category_id'] = null;
        } else {
            $category = Category::findOrFail($validated['category_id']);
            $validated['type'] = $category->type;
            $validated['to_wallet_id'] = null;
        }
        $validated['next_due_date'] = $validated['start_date'];
        $validated['is_active'] = true;
        $recurringData = collect($validated)->except('tags')->all();
        return DB::transaction(function () use ($validated, $recurringData, $wallet, $toWallet, $category, $type) {
            $recurring = Recurring::create($recurringData);
            if (!empty($validated['tags'])) {
                $tagIds = collect($validated['tags'])->map(function ($tagName) {
                    return Tag::findOrCreateByName($tagName)->id;
                });
                $recurring->tags()->sync($tagIds);
            }
            $auditData = [
                'type' => $type,
                'amount' => $recurring->amount,
                'frequency' => $recurring->frequency,
                'description' => $validated['description'] ?? null,
                'start_date' => $validated['start_date'],
            ];
            if ($type === 'transfer') {
                $auditData['from_wallet'] = $wallet->name;
                $auditData['to_wallet'] = $toWallet->name;
            } else {
                $auditData['wallet'] = $wallet->name;
                $auditData['category'] = $category->name;
            }
            AuditLog::record('recurring_created', null, null, $auditData);
            return $recurring;
        });
    }

    public function updateRecurring(Recurring $recurring, array $validated, bool $hasTags = false): bool
    {
        $hasTransactions = $recurring->generatedTransactions()->exists();
        $type = $hasTransactions ? $recurring->type : $validated['type'];
        $wallet = Wallet::findOrFail($validated['wallet_id']);
        $toWallet = null;
        $category = null;
        if ($type === 'transfer') {
            $toWallet = Wallet::findOrFail($validated['to_wallet_id']);
            $validated['category_id'] = null;
        } else {
            $category = Category::findOrFail($validated['category_id']);
            $validated['type'] = $category->type;
            $validated['to_wallet_id'] = null;
        }
        $oldValues = [
            'type' => $recurring->type,
            'wallet' => $recurring->wallet->name ?? 'Unknown',
            'amount' => $recurring->amount,
            'frequency' => $recurring->frequency,
            'description' => $recurring->description,
            'start_date' => $recurring->start_date ? $recurring->start_date->format('Y-m-d') : null,
            'is_active' => $recurring->is_active,
        ];
        if ($recurring->type === 'transfer') {
            $oldValues['to_wallet'] = $recurring->toWallet->name ?? 'Unknown';
        } else {
            $oldValues['category'] = $recurring->category->name ?? 'Unknown';
        }
        $recurringData = collect($validated)->except('tags')->all();
        $recurring->fill($recurringData);
        $tagsChanged = false;
        if ($hasTags) {
            $existingTags = $recurring->tags->pluck('name')->sort()->values()->all();
            $newTags = collect($validated['tags'] ?? [])->sort()->values()->all();
            if ($existingTags !== $newTags) {
                $tagsChanged = true;
            }
        }
        if (!$recurring->isDirty() && !$tagsChanged) {
            return false;
        }
        if ($recurring->isDirty(['start_date', 'frequency', 'end_date', 'is_active'])) {
            $today = now()->startOfDay();
            $newStartDate = Carbon::parse($recurring->start_date)->startOfDay();
            if ($newStartDate->gte($today)) {
                $recurring->next_due_date = $newStartDate;
            } else {
                if ($recurring->last_generated_at) {
                    $next = Carbon::parse($recurring->last_generated_at);
                    while ($next->lte($today)) {
                        $next = match ($recurring->frequency) {
                            'daily' => $next->addDay(),
                            'weekly' => $next->addWeek(),
                            'monthly' => $next->addMonthNoOverflow(),
                            'yearly' => $next->addYearNoOverflow(),
                            default => throw new InvalidArgumentException("Unknown frequency: {$recurring->frequency}"),
                        };
                    }
                    $recurring->next_due_date = $next;
                } else {
                    $recurring->next_due_date = $newStartDate;
                }
            }
            if ($recurring->end_date && $recurring->next_due_date && Carbon::parse($recurring->next_due_date)->gt(Carbon::parse($recurring->end_date)->endOfDay())) {
                $recurring->next_due_date = null;
                $recurring->is_active = false;
            } elseif ($recurring->is_active && $recurring->next_due_date !== null) {
                $recurring->is_active = true;
            }
        }
        DB::transaction(function () use ($recurring, $oldValues, $wallet, $toWallet, $category, $validated, $type) {
            if ($recurring->isDirty()) {
                $recurring->save();
            }
            $tagIds = [];
            if (isset($validated['tags']) && is_array($validated['tags'])) {
                $tagIds = collect($validated['tags'])->map(function ($tagName) {
                    return Tag::findOrCreateByName($tagName)->id;
                });
            }
            $recurring->tags()->sync($tagIds);
            $newValues = [
                'type' => $type,
                'amount' => $recurring->amount,
                'frequency' => $recurring->frequency,
                'description' => $recurring->description,
                'start_date' => $recurring->start_date ? $recurring->start_date->format('Y-m-d') : null,
                'is_active' => $recurring->is_active,
            ];
            if ($type === 'transfer') {
                $newValues['from_wallet'] = $wallet->name;
                $newValues['to_wallet'] = $toWallet->name;
            } else {
                $newValues['wallet'] = $wallet->name;
                $newValues['category'] = $category->name;
            }
            AuditLog::record('recurring_updated', null, $oldValues, $newValues);
        });
        return true;
    }

    public function deleteRecurring(Recurring $recurring, bool $deleteTransactions = false): void
    {
        $deletedInfo = [
            'type' => $recurring->type,
            'wallet' => $recurring->wallet->name ?? 'Unknown',
            'amount' => $recurring->amount,
            'frequency' => $recurring->frequency,
            'description' => $recurring->description,
        ];
        if ($recurring->type === 'transfer') {
            $deletedInfo['to_wallet'] = $recurring->toWallet->name ?? 'Unknown';
        } else {
            $deletedInfo['category'] = $recurring->category->name ?? 'Unknown';
        }
        DB::transaction(function () use ($recurring, $deleteTransactions, $deletedInfo) {
            if ($deleteTransactions) {
                $this->deleteGeneratedTransactions($recurring, 'Deleted via Recurring Rule cascade');
            }
            AuditLog::record('recurring_deleted', null, $deletedInfo, null);
            $recurring->delete();
        });
    }

    public function toggleStatus(Recurring $recurring): void
    {
        $oldValues = [
            'is_active' => $recurring->is_active,
        ];
        $recurring->is_active = !$recurring->is_active;
        if ($recurring->is_active) {
            $today = now()->startOfDay();
            if ($recurring->next_due_date && $recurring->next_due_date->lt($today)) {
                $date = $recurring->next_due_date->copy();
                while ($date->lt($today)) {
                    $date = match ($recurring->frequency) {
                        'daily' => $date->addDay(),
                        'weekly' => $date->addWeek(),
                        'monthly' => $date->addMonthNoOverflow(),
                        'yearly' => $date->addYearNoOverflow(),
                        default => throw new InvalidArgumentException("Unknown frequency: {$recurring->frequency}"),
                    };
                }
                if ($recurring->end_date && $date->greaterThan(Carbon::parse($recurring->end_date)->endOfDay())) {
                    $recurring->next_due_date = null;
                    $recurring->is_active = false;
                } else {
                    $recurring->next_due_date = $date;
                }
            } elseif ($recurring->next_due_date === null) {
                $next = $recurring->calculateNextDueDate();
                if ($next && (!$recurring->end_date || $next->lte(Carbon::parse($recurring->end_date)->endOfDay()))) {
                    $recurring->next_due_date = $next;
                } else {
                    $recurring->is_active = false;
                }
            }
        }
        DB::transaction(function () use ($recurring, $oldValues) {
            $recurring->save();
            $newValues = [
                'is_active' => $recurring->is_active,
            ];
            AuditLog::record('recurring_updated', null, $oldValues, $newValues);
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
                $outTx = $tx->type === 'transfer_out' ? $tx : $pair;
                $inTx = $tx->type === 'transfer_in' ? $tx : $pair;
                $outWallet = $lockedWallets->get($outTx->wallet_id) ?? $outTx->wallet;
                $inWallet = $lockedWallets->get($inTx->wallet_id) ?? $inTx->wallet;
                $outWallet?->revertBalance('transfer_out', (float) $outTx->amount);
                $inWallet?->revertBalance('transfer_in', (float) $inTx->amount);
                AuditLog::record('transfer_deleted', null, [
                    'from_wallet' => $outWallet->name ?? 'Unknown',
                    'to_wallet' => $inWallet->name ?? 'Unknown',
                    'amount' => $outTx->amount,
                    'note' => $auditNote,
                ], null);
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
                    'type' => $tx->type,
                    'amount' => $tx->amount,
                    'note' => $auditNote,
                ], null);
                $tx->delete();
            }
        }
    }
}