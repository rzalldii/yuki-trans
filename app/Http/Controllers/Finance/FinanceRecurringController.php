<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Finance\FinanceCategory;
use App\Models\Finance\FinanceRecurring;
use App\Models\Finance\FinanceTransaction;
use App\Models\Finance\FinanceWallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinanceRecurringController extends Controller
{
    public function index()
    {
        return redirect()->route('finance-settings.index', ['tab' => 'recurring']);
    }

    public function store(Request $request): JsonResponse
    {
        $request->merge([
            'description' => is_string($request->description) ? trim($request->description) : $request->description,
        ]);
        $validated = $request->validate([
            'wallet_id' => 'required|exists:finance_wallets,id',
            'category_id' => 'required|exists:finance_categories,id',
            'amount' => ['required', 'numeric', 'min:1'],
            'description' => 'nullable|string|max:1000',
            'frequency' => 'required|in:daily,weekly,monthly,yearly',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);
        $wallet = FinanceWallet::findOrFail($validated['wallet_id']);
        $category = FinanceCategory::findOrFail($validated['category_id']);
        $validated['type'] = $category->type;
        $validated['next_due_date'] = $validated['start_date'];
        $validated['is_active'] = true;
        DB::transaction(function () use ($validated, $wallet, $category) {
            $recurring = FinanceRecurring::create($validated);
            if ($recurring->is_active && $recurring->start_date->lte(now()->toDateString())) {
                $recurring->executeTransaction(auth()->id());
            }
            AuditLog::record('recurring_created', null, null, [
                'wallet' => $wallet->name,
                'category' => $category->name,
                'amount' => $recurring->amount,
                'frequency' => $recurring->frequency,
                'description' => $validated['description'] ?? null,
                'start_date' => $validated['start_date'],
            ]);
        });
        return response()->json([], 201);
    }

    public function edit(FinanceRecurring $financeRecurring): JsonResponse
    {
        return response()->json([
            'id' => $financeRecurring->id,
            'wallet_id' => $financeRecurring->wallet_id,
            'category_id' => $financeRecurring->category_id,
            'amount' => (int) $financeRecurring->amount,
            'description' => $financeRecurring->description,
            'frequency' => $financeRecurring->frequency,
            'start_date' => $financeRecurring->getRawOriginal('start_date'),
            'end_date' => $financeRecurring->getRawOriginal('end_date'),
            'is_active' => $financeRecurring->is_active,
        ]);
    }

    public function update(Request $request, FinanceRecurring $financeRecurring): JsonResponse
    {
        $request->merge([
            'description' => is_string($request->description) ? trim($request->description) : $request->description,
        ]);
        $validated = $request->validate([
            'wallet_id' => 'required|exists:finance_wallets,id',
            'category_id' => 'required|exists:finance_categories,id',
            'amount' => ['required', 'numeric', 'min:1'],
            'description' => 'nullable|string|max:1000',
            'frequency' => 'required|in:daily,weekly,monthly,yearly',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'is_active' => 'required|boolean',
        ]);
        $category = FinanceCategory::findOrFail($validated['category_id']);
        $wallet = FinanceWallet::findOrFail($validated['wallet_id']);
        $validated['type'] = $category->type;
        $oldValues = [
            'wallet' => $financeRecurring->wallet->name ?? 'Unknown',
            'category' => $financeRecurring->category->name ?? 'Unknown',
            'amount' => $financeRecurring->amount,
            'frequency' => $financeRecurring->frequency,
            'description' => $financeRecurring->description,
            'is_active' => $financeRecurring->is_active,
        ];
        $financeRecurring->fill($validated);
        if ($financeRecurring->isDirty(['start_date', 'frequency', 'end_date'])) {
            $nextDue = $financeRecurring->calculateNextDueDate();
            $financeRecurring->next_due_date = $nextDue;
            if ($nextDue === null) {
                $financeRecurring->is_active = false;
            }
        }
        if (!$financeRecurring->isDirty()) {
            return response()->json([], 204);
        }
        DB::transaction(function () use ($financeRecurring, $oldValues, $wallet, $category) {
            $financeRecurring->save();
            $newValues = [
                'wallet' => $wallet->name,
                'category' => $category->name,
                'amount' => $financeRecurring->amount,
                'frequency' => $financeRecurring->frequency,
                'description' => $financeRecurring->description,
                'is_active' => $financeRecurring->is_active,
            ];
            AuditLog::record('recurring_updated', null, $oldValues, $newValues);
        });
        return response()->json([], 200);
    }

    public function destroy(Request $request, FinanceRecurring $financeRecurring): JsonResponse
    {
        $deletedInfo = [
            'wallet' => $financeRecurring->wallet->name ?? 'Unknown',
            'category' => $financeRecurring->category->name ?? 'Unknown',
            'amount' => $financeRecurring->amount,
            'frequency' => $financeRecurring->frequency,
            'description' => $financeRecurring->description,
        ];
        DB::transaction(function () use ($request, $financeRecurring, $deletedInfo) {
            if ($request->boolean('delete_transactions')) {
                foreach ($financeRecurring->generatedTransactions as $tx) {
                    $wallet = FinanceWallet::find($tx->wallet_id);
                    if ($wallet) {
                        if ($tx->type === 'income') {
                            $wallet->decrement('current_balance', $tx->amount);
                        } else {
                            $wallet->increment('current_balance', $tx->amount);
                        }
                    }
                    AuditLog::record('transaction_deleted', null, [
                        'wallet' => $wallet->name ?? 'Unknown',
                        'category' => $tx->category->name ?? 'Unknown',
                        'type' => $tx->type,
                        'amount' => $tx->amount,
                        'note' => 'Deleted via Recurring Rule cascade',
                    ], null);
                    $tx->delete();
                }
            }
            AuditLog::record('recurring_deleted', null, $deletedInfo, null);
            $financeRecurring->delete();
        });
        return response()->json([], 200);
    }

    public function toggleStatus(FinanceRecurring $financeRecurring): JsonResponse
    {
        $oldValues = [
            'is_active' => $financeRecurring->is_active,
        ];
        $financeRecurring->is_active = !$financeRecurring->is_active;
        if ($financeRecurring->is_active && $financeRecurring->next_due_date && $financeRecurring->next_due_date->lt(now()->startOfDay())) {
            $date = $financeRecurring->next_due_date->copy();
            $today = now()->startOfDay();
            while ($date->lt($today)) {
                $date = match ($financeRecurring->frequency) {
                    'daily' => $date->addDay(),
                    'weekly' => $date->addWeek(),
                    'monthly' => $date->addMonth(),
                    'yearly' => $date->addYear(),
                };
            }
            if ($financeRecurring->end_date && $date->greaterThan($financeRecurring->end_date)) {
                $financeRecurring->next_due_date = null;
                $financeRecurring->is_active = false;
            } else {
                $financeRecurring->next_due_date = $date;
            }
        }
        DB::transaction(function () use ($financeRecurring, $oldValues) {
            $financeRecurring->save();
            $newValues = [
                'is_active' => $financeRecurring->is_active,
            ];
            AuditLog::record('recurring_updated', null, $oldValues, $newValues);
        });
        return response()->json([], 200);
    }

    public function generate(): JsonResponse
    {
        $dueRecurrings = FinanceRecurring::active()
            ->dueOn(now()->toDateString())
            ->with(['wallet', 'category'])
            ->get();
        $generated = 0;
        $userId = auth()->id();
        DB::transaction(function () use ($dueRecurrings, &$generated, $userId) {
            foreach ($dueRecurrings as $recurring) {
                $recurring->executeTransaction($userId);
                $generated++;
            }
            if ($generated > 0) {
                AuditLog::record('recurring_generated', null, null, [
                    'count' => $generated,
                    'date' => now()->toDateString(),
                ]);
            }
        });
        return response()->json([], 200);
    }
}