<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Enums\CategoryType;
use App\Enums\Frequency;
use App\Enums\RecurringType;
use App\Models\Audit\AuditLog;
use App\Models\Finance\Category;
use App\Models\Finance\Recurring;
use App\Models\Finance\Tag;
use App\Models\Finance\Transaction;
use App\Models\Finance\Wallet;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RecurringService
{
    public function __construct(
        protected RecurringExecutionService $executionService
    ) {}

    public function createRecurring(array $validated): Recurring
    {
        $type = $validated['type'];
        $wallet = Wallet::findOrFail($validated['wallet_id']);
        $toWallet = null;
        $category = null;
        $isTransfer = $type === RecurringType::Transfer->value || $type === RecurringType::Transfer || $type === 'transfer';
        if ($isTransfer) {
            $toWallet = Wallet::findOrFail($validated['to_wallet_id']);
            $validated['category_id'] = null;
            $validated['type'] = RecurringType::Transfer->value;
        } else {
            $category = Category::findOrFail($validated['category_id']);
            $catType = $category->type instanceof CategoryType ? $category->type->value : (string) $category->type;
            $validated['type'] = $catType;
            $validated['to_wallet_id'] = null;
        }
        $validated['next_due_date'] = $validated['start_date'];
        $validated['is_active'] = true;
        $recurringData = collect($validated)->except('tags')->all();
        return DB::transaction(function () use ($validated, $recurringData, $wallet, $toWallet, $category, $isTransfer) {
            $recurring = Recurring::create($recurringData);
            if (!empty($validated['tags'])) {
                $tagIds = collect($validated['tags'])->map(function ($tagName) {
                    return Tag::findOrCreateByName($tagName)->id;
                });
                $recurring->tags()->sync($tagIds);
            }
            $freqVal = $recurring->frequency instanceof Frequency ? $recurring->frequency->value : $recurring->frequency;
            $typeVal = $recurring->type instanceof RecurringType ? $recurring->type->value : $recurring->type;
            $auditData = [
                'type' => $typeVal,
                'amount' => $recurring->amount,
                'frequency' => $freqVal,
                'description' => $validated['description'] ?? null,
                'start_date' => $validated['start_date'],
            ];
            if ($isTransfer) {
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
        $isTransfer = $type === RecurringType::Transfer || $type === RecurringType::Transfer->value || $type === 'transfer';
        if ($isTransfer) {
            $toWallet = Wallet::findOrFail($validated['to_wallet_id']);
            $validated['category_id'] = null;
            $validated['type'] = RecurringType::Transfer->value;
        } else {
            $category = Category::findOrFail($validated['category_id']);
            $catType = $category->type instanceof CategoryType ? $category->type->value : (string) $category->type;
            $validated['type'] = $catType;
            $validated['to_wallet_id'] = null;
        }
        $recTypeVal = $recurring->type instanceof RecurringType ? $recurring->type->value : $recurring->type;
        $recFreqVal = $recurring->frequency instanceof Frequency ? $recurring->frequency->value : $recurring->frequency;
        $oldValues = [
            'type' => $recTypeVal,
            'wallet' => $recurring->wallet->name ?? 'Unknown',
            'amount' => $recurring->amount,
            'frequency' => $recFreqVal,
            'description' => $recurring->description,
            'start_date' => $recurring->start_date ? $recurring->start_date->format('Y-m-d') : null,
            'is_active' => $recurring->is_active,
        ];
        if ($isTransfer) {
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
                    $freq = $recurring->frequency instanceof Frequency ? $recurring->frequency : Frequency::from((string) $recurring->frequency);
                    while ($next->lte($today)) {
                        $next = $freq->addToDate($next);
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
        DB::transaction(function () use ($recurring, $oldValues, $wallet, $toWallet, $category, $validated, $isTransfer) {
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
            $newFreqVal = $recurring->frequency instanceof Frequency ? $recurring->frequency->value : $recurring->frequency;
            $newTypeVal = $recurring->type instanceof RecurringType ? $recurring->type->value : $recurring->type;
            $newValues = [
                'type' => $newTypeVal,
                'amount' => $recurring->amount,
                'frequency' => $newFreqVal,
                'description' => $recurring->description,
                'start_date' => $recurring->start_date ? $recurring->start_date->format('Y-m-d') : null,
                'is_active' => $recurring->is_active,
            ];
            if ($isTransfer) {
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
        $isTransfer = $recurring->type === RecurringType::Transfer || $recurring->type === RecurringType::Transfer->value || $recurring->type === 'transfer';
        $recTypeVal = $recurring->type instanceof RecurringType ? $recurring->type->value : $recurring->type;
        $recFreqVal = $recurring->frequency instanceof Frequency ? $recurring->frequency->value : $recurring->frequency;
        $deletedInfo = [
            'type' => $recTypeVal,
            'wallet' => $recurring->wallet->name ?? 'Unknown',
            'amount' => $recurring->amount,
            'frequency' => $recFreqVal,
            'description' => $recurring->description,
        ];
        if ($isTransfer) {
            $deletedInfo['to_wallet'] = $recurring->toWallet->name ?? 'Unknown';
        } else {
            $deletedInfo['category'] = $recurring->category->name ?? 'Unknown';
        }
        DB::transaction(function () use ($recurring, $deleteTransactions, $deletedInfo) {
            if ($deleteTransactions) {
                $this->executionService->deleteGeneratedTransactions($recurring, 'Deleted via Recurring Rule cascade');
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
                $freq = $recurring->frequency instanceof Frequency ? $recurring->frequency : Frequency::from((string) $recurring->frequency);
                while ($date->lt($today)) {
                    $date = $freq->addToDate($date);
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

    public function processDueRecurrings(?int $userId = null): int
    {
        return $this->executionService->processDueRecurrings($userId);
    }

    public function executeRecurring(Recurring $recurring, ?int $userId = null): ?Transaction
    {
        return $this->executionService->executeRecurring($recurring, $userId);
    }

    public function deleteGeneratedTransactions(Recurring $recurring, string $auditNote): void
    {
        $this->executionService->deleteGeneratedTransactions($recurring, $auditNote);
    }
}