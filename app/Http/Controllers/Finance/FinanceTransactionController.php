<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Finance\FinanceCategory;
use App\Models\Finance\FinanceTransaction;
use App\Models\Finance\FinanceWallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinanceTransactionController extends Controller
{
    public function index(Request $request)
    {
        $categories = FinanceCategory::orderBy('name')->get();
        $transactions = FinanceTransaction::with(['user', 'category'])->orderBy('transaction_date', 'desc')->get();
        $filterCategories = $categories->pluck('name')->unique()->sort()->values();
        $filterTypes = $categories->pluck('type')->unique()->sort()->values();
        $currentMonthStart = now()->startOfMonth()->toDateString();
        $currentMonthEnd = now()->endOfMonth()->toDateString();
        $currentMonthLabel = now()->translatedFormat('F Y');
        $monthlySummary = FinanceTransaction::join('finance_categories', 'finance_transactions.category_id', '=', 'finance_categories.id')
            ->whereBetween('finance_transactions.transaction_date', [$currentMonthStart, $currentMonthEnd])
            ->selectRaw("
                SUM(CASE WHEN finance_categories.type = 'income' THEN CAST(finance_transactions.amount AS UNSIGNED) ELSE 0 END) as total_income,
                SUM(CASE WHEN finance_categories.type = 'expense' THEN CAST(finance_transactions.amount AS UNSIGNED) ELSE 0 END) as total_expense
            ")
            ->first();
        $totalIncome = (int) ($monthlySummary->total_income ?? 0);
        $totalExpense = (int) ($monthlySummary->total_expense ?? 0);
        $allTimeSummary = FinanceTransaction::join('finance_categories', 'finance_transactions.category_id', '=', 'finance_categories.id')
            ->selectRaw("
                SUM(CASE WHEN finance_categories.type = 'income' THEN CAST(finance_transactions.amount AS UNSIGNED) ELSE 0 END) as all_income,
                SUM(CASE WHEN finance_categories.type = 'expense' THEN CAST(finance_transactions.amount AS UNSIGNED) ELSE 0 END) as all_expense
            ")
            ->first();
        $walletBalance = (int) FinanceWallet::sum('initial_balance');
        $netBalance = $walletBalance + (int) ($allTimeSummary->all_income ?? 0) - (int) ($allTimeSummary->all_expense ?? 0);
        return view('pages.finance.finance-transaction', compact(
            'categories', 'transactions', 'filterCategories', 'filterTypes',
            'totalIncome', 'totalExpense', 'netBalance', 'currentMonthLabel'
        ));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:finance_categories,id',
            'amount' => ['required', 'string', 'regex:/^\d+$/'],
            'description' => 'nullable|string|max:1000',
            'transaction_date' => 'required|date',
        ]);
        $category = FinanceCategory::findOrFail($validated['category_id']);
        $validated['user_id'] = auth()->id();
        $transaction = FinanceTransaction::create($validated);
        AuditLog::record('transaction_created', null, null, [
            'category' => $category->name,
            'amount' => $transaction->amount,
            'transaction_date' => $validated['transaction_date'],
        ]);
        return response()->json([], 201);
    }

    public function edit(FinanceTransaction $financeTransaction): JsonResponse
    {
        if (!auth()->user()->isAdmin() && $financeTransaction->user_id !== auth()->id()) {
            return response()->json([], 403);
        }
        return response()->json([
            'id' => $financeTransaction->id,
            'category_id' => $financeTransaction->category_id,
            'amount' => $financeTransaction->amount,
            'description' => $financeTransaction->description,
            'transaction_date' => $financeTransaction->getRawOriginal('transaction_date'),
        ]);
    }

    public function update(Request $request, FinanceTransaction $financeTransaction): JsonResponse
    {
        if (!auth()->user()->isAdmin() && $financeTransaction->user_id !== auth()->id()) {
            return response()->json([], 403);
        }
        $validated = $request->validate([
            'category_id' => 'required|exists:finance_categories,id',
            'amount' => ['required', 'string', 'regex:/^\d+$/'],
            'description' => 'nullable|string|max:1000',
            'transaction_date' => 'required|date',
        ]);
        $category = FinanceCategory::findOrFail($validated['category_id']);
        $oldCategory = $financeTransaction->category;
        $oldValues = [
            'category' => $oldCategory->name ?? 'Unknown',
            'amount' => $financeTransaction->amount,
            'transaction_date' => $financeTransaction->getRawOriginal('transaction_date'),
        ];
        $financeTransaction->fill($validated);
        if (!$financeTransaction->isDirty()) {
            return response()->json([], 204);
        }
        $financeTransaction->save();
        $newValues = [
            'category' => $category->name,
            'amount' => $financeTransaction->amount,
            'transaction_date' => $validated['transaction_date'],
        ];
        AuditLog::record('transaction_updated', null, $oldValues, $newValues);
        return response()->json([], 200);
    }

    public function destroy(FinanceTransaction $financeTransaction): JsonResponse
    {
        if (!auth()->user()->isAdmin() && $financeTransaction->user_id !== auth()->id()) {
            return response()->json([], 403);
        }
        $category = $financeTransaction->category;
        $deletedInfo = [
            'category' => $category->name ?? 'Unknown',
            'amount' => $financeTransaction->amount,
            'transaction_date' => $financeTransaction->getRawOriginal('transaction_date'),
        ];
        AuditLog::record('transaction_deleted', null, $deletedInfo, null);
        $financeTransaction->delete();
        return response()->json([], 200);
    }
}