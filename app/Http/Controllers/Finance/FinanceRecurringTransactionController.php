<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Finance\FinanceCategory;
use App\Models\Finance\FinanceRecurringTransaction;
use App\Models\Finance\FinanceTransaction;
use App\Models\Finance\FinanceWallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinanceRecurringTransactionController extends Controller
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
        $category = FinanceCategory::findOrFail($validated['category_id']);
        $validated['type'] = $category->type;
        $validated['next_due_date'] = $validated['start_date'];
        $validated['is_active'] = true;
        $recurring = DB::transaction(function () use ($validated) {
            $recurring = FinanceRecurringTransaction::create($validated);
            if ($recurring->is_active && $recurring->start_date->lte(now()->toDateString())) {
                $recurring->executeTransaction(auth()->id());
            }
            return $recurring;
        });
        AuditLog::record('recurring_created', null, null, [
            'category' => $category->name,
            'amount' => $recurring->amount,
            'frequency' => $recurring->frequency,
            'start_date' => $validated['start_date'],
        ]);
        return response()->json([], 201);
    }

    public function edit(FinanceRecurringTransaction $financeRecurring): JsonResponse
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

    public function update(Request $request, FinanceRecurringTransaction $financeRecurring): JsonResponse
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
        $validated['type'] = $category->type;
        $oldValues = [
            'amount' => $financeRecurring->amount,
            'frequency' => $financeRecurring->frequency,
            'is_active' => $financeRecurring->is_active,
        ];
        $financeRecurring->fill($validated);
        if (!$financeRecurring->isDirty()) {
            return response()->json([], 204);
        }
        $financeRecurring->save();
        $newValues = [
            'amount' => $financeRecurring->amount,
            'frequency' => $financeRecurring->frequency,
            'is_active' => $financeRecurring->is_active,
        ];
        AuditLog::record('recurring_updated', null, $oldValues, $newValues);
        return response()->json([], 200);
    }

    public function destroy(FinanceRecurringTransaction $financeRecurring): JsonResponse
    {
        $deletedInfo = [
            'amount' => $financeRecurring->amount,
            'frequency' => $financeRecurring->frequency,
        ];
        AuditLog::record('recurring_deleted', null, $deletedInfo, null);
        $financeRecurring->delete();
        return response()->json([], 200);
    }

    public function generate(): JsonResponse
    {
        $dueRecurrings = FinanceRecurringTransaction::active()
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
        });
        if ($generated > 0) {
            AuditLog::record('recurring_generated', null, null, [
                'count' => $generated,
                'date' => now()->toDateString(),
            ]);
        }
        return response()->json(['generated' => $generated], 200);
    }
}
