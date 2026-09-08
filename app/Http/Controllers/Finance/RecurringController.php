<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreRecurringRequest;
use App\Http\Requests\Finance\UpdateRecurringRequest;
use App\Models\Audit\AuditLog;
use App\Models\Finance\Category;
use App\Models\Finance\Recurring;
use App\Models\Finance\Tag;
use App\Models\Finance\Transaction;
use App\Models\Finance\Wallet;
use App\Services\Finance\RecurringService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class RecurringController extends Controller
{
    public function index()
    {
        return redirect()->route('finance-master-data.index', ['tab' => 'recurring']);
    }

    public function store(StoreRecurringRequest $request): JsonResponse
    {
        Gate::authorize('create', Recurring::class);
        $validated = $request->validated();
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
        DB::transaction(function () use ($validated, $recurringData, $wallet, $toWallet, $category, $type) {
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
        });
        return response()->json(['success' => true], 201);
    }

    public function edit(Recurring $financeRecurring): JsonResponse
    {
        Gate::authorize('view', $financeRecurring);
        $hasTransactions = $financeRecurring->generatedTransactions()->exists();
        return response()->json([
            'id' => $financeRecurring->id,
            'type' => $financeRecurring->type,
            'wallet_id' => $financeRecurring->wallet_id,
            'to_wallet_id' => $financeRecurring->to_wallet_id,
            'category_id' => $financeRecurring->category_id,
            'amount' => (int) $financeRecurring->amount,
            'description' => $financeRecurring->description,
            'frequency' => $financeRecurring->frequency,
            'start_date' => $financeRecurring->start_date ? $financeRecurring->start_date->format('Y-m-d') : null,
            'end_date' => $financeRecurring->end_date ? $financeRecurring->end_date->format('Y-m-d') : null,
            'is_active' => $financeRecurring->is_active,
            'tags' => $financeRecurring->tags->pluck('name'),
            'last_generated_at' => $financeRecurring->last_generated_at ? $financeRecurring->last_generated_at->format('Y-m-d') : null,
            'has_transactions' => $hasTransactions,
        ]);
    }

    public function update(UpdateRecurringRequest $request, Recurring $financeRecurring): JsonResponse
    {
        Gate::authorize('update', $financeRecurring);
        $hasTransactions = $financeRecurring->generatedTransactions()->exists();
        $validated = $request->validated();
        $type = $hasTransactions ? $financeRecurring->type : $validated['type'];
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
            'type' => $financeRecurring->type,
            'wallet' => $financeRecurring->wallet->name ?? 'Unknown',
            'amount' => $financeRecurring->amount,
            'frequency' => $financeRecurring->frequency,
            'description' => $financeRecurring->description,
            'start_date' => $financeRecurring->start_date ? $financeRecurring->start_date->format('Y-m-d') : null,
            'is_active' => $financeRecurring->is_active,
        ];
        if ($financeRecurring->type === 'transfer') {
            $oldValues['to_wallet'] = $financeRecurring->toWallet->name ?? 'Unknown';
        } else {
            $oldValues['category'] = $financeRecurring->category->name ?? 'Unknown';
        }
        $recurringData = collect($validated)->except('tags')->all();
        $financeRecurring->fill($recurringData);
        $tagsChanged = false;
        if ($request->has('tags')) {
            $existingTags = $financeRecurring->tags->pluck('name')->sort()->values()->all();
            $newTags = collect($validated['tags'] ?? [])->sort()->values()->all();
            if ($existingTags !== $newTags) {
                $tagsChanged = true;
            }
        }
        if (!$financeRecurring->isDirty() && !$tagsChanged) {
            return response()->json([], 204);
        }
        if ($financeRecurring->isDirty(['start_date', 'frequency', 'end_date', 'is_active'])) {
            $today = now()->startOfDay();
            $newStartDate = Carbon::parse($financeRecurring->start_date)->startOfDay();
            if ($newStartDate->gte($today)) {
                $financeRecurring->next_due_date = $newStartDate;
            } else {
                if ($financeRecurring->last_generated_at) {
                    $next = Carbon::parse($financeRecurring->last_generated_at);
                    while ($next->lte($today)) {
                        $next = match ($financeRecurring->frequency) {
                            'daily' => $next->addDay(),
                            'weekly' => $next->addWeek(),
                            'monthly' => $next->addMonthNoOverflow(),
                            'yearly' => $next->addYearNoOverflow(),
                            default => throw new \InvalidArgumentException("Unknown frequency: {$financeRecurring->frequency}"),
                        };
                    }
                    $financeRecurring->next_due_date = $next;
                } else {
                    $financeRecurring->next_due_date = $newStartDate;
                }
            }
            if ($financeRecurring->end_date && $financeRecurring->next_due_date && Carbon::parse($financeRecurring->next_due_date)->gt(Carbon::parse($financeRecurring->end_date)->endOfDay())) {
                $financeRecurring->next_due_date = null;
                $financeRecurring->is_active = false;
            } elseif ($financeRecurring->is_active && $financeRecurring->next_due_date !== null) {
                $financeRecurring->is_active = true;
            }
        }
        DB::transaction(function () use ($financeRecurring, $oldValues, $wallet, $toWallet, $category, $validated, $type) {
            if ($financeRecurring->isDirty()) {
                $financeRecurring->save();
            }
            $tagIds = [];
            if (isset($validated['tags']) && is_array($validated['tags'])) {
                $tagIds = collect($validated['tags'])->map(function ($tagName) {
                    return Tag::findOrCreateByName($tagName)->id;
                });
            }
            $financeRecurring->tags()->sync($tagIds);
            $newValues = [
                'type' => $type,
                'amount' => $financeRecurring->amount,
                'frequency' => $financeRecurring->frequency,
                'description' => $financeRecurring->description,
                'start_date' => $financeRecurring->start_date ? $financeRecurring->start_date->format('Y-m-d') : null,
                'is_active' => $financeRecurring->is_active,
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
        return response()->json(['success' => true], 200);
    }

    public function destroy(Request $request, Recurring $financeRecurring): JsonResponse
    {
        Gate::authorize('delete', $financeRecurring);
        $deletedInfo = [
            'type' => $financeRecurring->type,
            'wallet' => $financeRecurring->wallet->name ?? 'Unknown',
            'amount' => $financeRecurring->amount,
            'frequency' => $financeRecurring->frequency,
            'description' => $financeRecurring->description,
        ];
        if ($financeRecurring->type === 'transfer') {
            $deletedInfo['to_wallet'] = $financeRecurring->toWallet->name ?? 'Unknown';
        } else {
            $deletedInfo['category'] = $financeRecurring->category->name ?? 'Unknown';
        }
        DB::transaction(function () use ($request, $financeRecurring, $deletedInfo) {
            if ($request->boolean('delete_transactions')) {
                $this->deleteGeneratedTransactions($financeRecurring, 'Deleted via Recurring Rule cascade');
            }
            AuditLog::record('recurring_deleted', null, $deletedInfo, null);
            $financeRecurring->delete();
        });
        return response()->json(['success' => true], 200);
    }

    public function toggleStatus(Recurring $financeRecurring): JsonResponse
    {
        Gate::authorize('update', $financeRecurring);
        $oldValues = [
            'is_active' => $financeRecurring->is_active,
        ];
        $financeRecurring->is_active = !$financeRecurring->is_active;
        if ($financeRecurring->is_active) {
            $today = now()->startOfDay();
            if ($financeRecurring->next_due_date && $financeRecurring->next_due_date->lt($today)) {
                $date = $financeRecurring->next_due_date->copy();
                while ($date->lt($today)) {
                    $date = match ($financeRecurring->frequency) {
                        'daily' => $date->addDay(),
                        'weekly' => $date->addWeek(),
                        'monthly' => $date->addMonthNoOverflow(),
                        'yearly' => $date->addYearNoOverflow(),
                        default => throw new \InvalidArgumentException("Unknown frequency: {$financeRecurring->frequency}"),
                    };
                }
                if ($financeRecurring->end_date && $date->greaterThan(Carbon::parse($financeRecurring->end_date)->endOfDay())) {
                    $financeRecurring->next_due_date = null;
                    $financeRecurring->is_active = false;
                } else {
                    $financeRecurring->next_due_date = $date;
                }
            } elseif ($financeRecurring->next_due_date === null) {
                $next = $financeRecurring->calculateNextDueDate();
                if ($next && (!$financeRecurring->end_date || $next->lte(Carbon::parse($financeRecurring->end_date)->endOfDay()))) {
                    $financeRecurring->next_due_date = $next;
                } else {
                    $financeRecurring->is_active = false;
                }
            }
        }
        DB::transaction(function () use ($financeRecurring, $oldValues) {
            $financeRecurring->save();
            $newValues = [
                'is_active' => $financeRecurring->is_active,
            ];
            AuditLog::record('recurring_updated', null, $oldValues, $newValues);
        });
        return response()->json(['success' => true], 200);
    }

    private function deleteGeneratedTransactions(Recurring $recurring, string $auditNote): void
    {
        $processedPairs = [];
        foreach ($recurring->generatedTransactions()->with(['transferPair.wallet', 'wallet', 'category'])->get() as $tx) {
            if ($tx->isTransfer() && $tx->transferPair) {
                if (isset($processedPairs[$tx->id])) {
                    continue;
                }
                $pair = $tx->transferPair;
                $processedPairs[$tx->id] = true;
                $processedPairs[$pair->id] = true;
                $outTx = $tx->type === 'transfer_out' ? $tx : $pair;
                $inTx = $tx->type === 'transfer_in' ? $tx : $pair;
                $outTx->wallet?->revertBalance('transfer_out', (float) $outTx->amount);
                $inTx->wallet?->revertBalance('transfer_in', (float) $inTx->amount);
                AuditLog::record('transfer_deleted', null, [
                    'from_wallet' => $outTx->wallet->name ?? 'Unknown',
                    'to_wallet' => $inTx->wallet->name ?? 'Unknown',
                    'amount' => $outTx->amount,
                    'note' => $auditNote,
                ], null);
                $pair->update(['transfer_pair_id' => null]);
                $tx->update(['transfer_pair_id' => null]);
                $pair->delete();
                $tx->delete();
            } else {
                $w = $tx->wallet;
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

    public function generate(RecurringService $service): JsonResponse
    {
        if (!app()->runningInConsole() && !auth()->user()?->isAdmin()) {
            return response()->json(['success' => false], 403);
        }
        $generated = $service->processDueRecurrings(auth()->id());
        return response()->json([
            'success' => true,
            'generated' => $generated,
        ], 200);
    }
}