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
        $wallets = FinanceWallet::orderBy('name')->get();
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->endOfMonth()->toDateString());
        $transactions = FinanceTransaction::with(['user', 'wallet', 'category'])
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->orderBy('transaction_date', 'desc')->get();
        $filterCategories = $categories->pluck('name')->unique()->sort()->values();
        $filterTypes = $categories->pluck('type')->unique()->sort()->values();
        $currentMonthLabel = \Carbon\Carbon::parse($startDate)->translatedFormat('d M Y') . ' - ' . \Carbon\Carbon::parse($endDate)->translatedFormat('d M Y');
        $monthlySummary = FinanceTransaction::join('finance_categories', 'finance_transactions.category_id', '=', 'finance_categories.id')
            ->whereBetween('finance_transactions.transaction_date', [$startDate, $endDate])
            ->selectRaw("
                SUM(CASE WHEN finance_categories.type = 'income' THEN finance_transactions.amount ELSE 0 END) as total_income,
                SUM(CASE WHEN finance_categories.type = 'expense' THEN finance_transactions.amount ELSE 0 END) as total_expense
            ")
            ->first();
        $totalIncome = (float) ($monthlySummary->total_income ?? 0);
        $totalExpense = (float) ($monthlySummary->total_expense ?? 0);
        $allTimeSummary = FinanceTransaction::join('finance_categories', 'finance_transactions.category_id', '=', 'finance_categories.id')
            ->selectRaw("
                SUM(CASE WHEN finance_categories.type = 'income' THEN finance_transactions.amount ELSE 0 END) as all_income,
                SUM(CASE WHEN finance_categories.type = 'expense' THEN finance_transactions.amount ELSE 0 END) as all_expense
            ")
            ->first();
        $walletBalance = (float) FinanceWallet::sum('initial_balance');
        $netBalance = $walletBalance + (float) ($allTimeSummary->all_income ?? 0) - (float) ($allTimeSummary->all_expense ?? 0);
        return view('pages.finance.finance-transaction', compact(
            'wallets', 'categories', 'transactions', 'filterCategories', 'filterTypes',
            'totalIncome', 'totalExpense', 'netBalance', 'currentMonthLabel',
            'startDate', 'endDate'
        ));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'wallet_id' => 'required|exists:finance_wallets,id',
            'category_id' => 'required|exists:finance_categories,id',
            'amount' => ['required', 'numeric', 'min:0'],
            'description' => 'nullable|string|max:1000',
            'transaction_date' => 'required|date',
        ]);
        $wallet = FinanceWallet::findOrFail($validated['wallet_id']);
        $category = FinanceCategory::findOrFail($validated['category_id']);
        $validated['user_id'] = auth()->id();
        $transaction = FinanceTransaction::create($validated);
        AuditLog::record('transaction_created', null, null, [
            'wallet' => $wallet->name,
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
            'wallet_id' => $financeTransaction->wallet_id,
            'category_id' => $financeTransaction->category_id,
            'amount' => (int) $financeTransaction->amount,
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
            'wallet_id' => 'required|exists:finance_wallets,id',
            'category_id' => 'required|exists:finance_categories,id',
            'amount' => ['required', 'numeric', 'min:0'],
            'description' => 'nullable|string|max:1000',
            'transaction_date' => 'required|date',
        ]);
        $wallet = FinanceWallet::findOrFail($validated['wallet_id']);
        $category = FinanceCategory::findOrFail($validated['category_id']);
        $oldWallet = $financeTransaction->wallet;
        $oldCategory = $financeTransaction->category;
        $oldValues = [
            'wallet' => $oldWallet->name ?? 'Unknown',
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
            'wallet' => $wallet->name,
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
        $wallet = $financeTransaction->wallet;
        $category = $financeTransaction->category;
        $deletedInfo = [
            'wallet' => $wallet->name ?? 'Unknown',
            'category' => $category->name ?? 'Unknown',
            'amount' => $financeTransaction->amount,
            'transaction_date' => $financeTransaction->getRawOriginal('transaction_date'),
        ];
        AuditLog::record('transaction_deleted', null, $deletedInfo, null);
        $financeTransaction->delete();
        return response()->json([], 200);
    }
}