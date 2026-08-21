<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Finance\FinanceCategory;
use App\Models\Finance\FinanceTag;
use App\Models\Finance\FinanceTransaction;
use App\Models\Finance\FinanceWallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinanceTransactionController extends Controller
{
    public function index(Request $request)
    {
        $categories = FinanceCategory::orderBy('name')->get();
        $wallets = FinanceWallet::orderBy('name')->get();
        $tags = FinanceTag::orderBy('name')->get();
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->endOfMonth()->toDateString());
        $ledger = FinanceTransaction::with(['user', 'wallet', 'category', 'transferPair.wallet', 'tags'])
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('type', '!=', 'transfer_in')
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->get();
        $monthlySummary = FinanceTransaction::whereBetween('transaction_date', [$startDate, $endDate])
            ->selectRaw("
                SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as total_income,
                SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as total_expense
            ")
            ->first();
        $totalIncome = (float) ($monthlySummary->total_income ?? 0);
        $totalExpense = (float) ($monthlySummary->total_expense ?? 0);
        $netBalance = (float) FinanceWallet::sum('current_balance');
        $filterCategories = $categories->pluck('name')->unique()->sort()->values();
        $filterTypes = collect(['income', 'expense', 'transfer']);
        $filterTags = $tags->pluck('name')->unique()->sort()->values();
        $currentMonthLabel = \Carbon\Carbon::parse($startDate)->translatedFormat('d M Y') . ' - ' . \Carbon\Carbon::parse($endDate)->translatedFormat('d M Y');
        return view('pages.finance.finance-transaction', compact(
            'wallets', 'categories', 'tags', 'ledger', 'filterCategories', 'filterTypes', 'filterTags',
            'totalIncome', 'totalExpense', 'netBalance', 'currentMonthLabel',
            'startDate', 'endDate'
        ));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'wallet_id' => 'required|exists:finance_wallets,id',
            'category_id' => 'required|exists:finance_categories,id',
            'amount' => ['required', 'numeric', 'min:1'],
            'description' => 'nullable|string|max:1000',
            'transaction_date' => 'required|date',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
        ]);
        $wallet = FinanceWallet::findOrFail($validated['wallet_id']);
        $category = FinanceCategory::findOrFail($validated['category_id']);
        $validated['user_id'] = auth()->id();
        $validated['type'] = $category->type;
        $transaction = null;
        DB::transaction(function () use (&$transaction, $validated, $wallet, $request) {
            $transaction = FinanceTransaction::create($validated);
            if ($validated['type'] === 'income') {
                $wallet->increment('current_balance', $validated['amount']);
            } else {
                $wallet->decrement('current_balance', $validated['amount']);
            }
            if ($request->filled('tags')) {
                $tagIds = collect($request->tags)->map(function ($tagName) {
                    return FinanceTag::firstOrCreate(
                        ['name' => trim($tagName)],
                        ['user_id' => auth()->id(), 'color' => '#696CFF']
                    )->id;
                });
                $transaction->tags()->sync($tagIds);
            }
        });
        AuditLog::record('transaction_created', null, null, [
            'wallet' => $wallet->name,
            'category' => $category->name,
            'type' => $validated['type'],
            'amount' => $transaction->amount,
            'transaction_date' => $validated['transaction_date'],
        ]);

        return response()->json([], 201);
    }

    public function storeTransfer(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from_wallet_id' => 'required|exists:finance_wallets,id',
            'to_wallet_id' => 'required|exists:finance_wallets,id|different:from_wallet_id',
            'amount' => ['required', 'numeric', 'min:1'],
            'description' => 'nullable|string|max:1000',
            'transaction_date' => 'required|date',
        ]);
        $fromWallet = FinanceWallet::findOrFail($validated['from_wallet_id']);
        $toWallet = FinanceWallet::findOrFail($validated['to_wallet_id']);
        DB::transaction(function () use ($validated, $fromWallet, $toWallet) {
            $out = FinanceTransaction::create([
                'user_id' => auth()->id(),
                'wallet_id' => $fromWallet->id,
                'type' => 'transfer_out',
                'amount' => $validated['amount'],
                'description' => $validated['description'],
                'transaction_date' => $validated['transaction_date'],
            ]);
            $in = FinanceTransaction::create([
                'user_id' => auth()->id(),
                'wallet_id' => $toWallet->id,
                'type' => 'transfer_in',
                'amount' => $validated['amount'],
                'description' => $validated['description'],
                'transaction_date' => $validated['transaction_date'],
            ]);
            $out->update(['transfer_pair_id' => $in->id]);
            $in->update(['transfer_pair_id' => $out->id]);
            $fromWallet->decrement('current_balance', $validated['amount']);
            $toWallet->increment('current_balance', $validated['amount']);
        });
        AuditLog::record('transfer_created', null, null, [
            'from_wallet' => $fromWallet->name,
            'to_wallet' => $toWallet->name,
            'amount' => $validated['amount'],
            'transfer_date' => $validated['transaction_date'],
        ]);
        return response()->json([], 201);
    }

    public function edit(FinanceTransaction $financeTransaction): JsonResponse
    {
        if (!auth()->user()->isAdmin() && $financeTransaction->user_id !== auth()->id()) {
            return response()->json([], 403);
        }
        $data = [
            'id' => $financeTransaction->id,
            'type' => $financeTransaction->type,
            'wallet_id' => $financeTransaction->wallet_id,
            'category_id' => $financeTransaction->category_id,
            'amount' => (int) $financeTransaction->amount,
            'description' => $financeTransaction->description,
            'transaction_date' => $financeTransaction->getRawOriginal('transaction_date'),
            'tags' => $financeTransaction->tags->pluck('name'),
        ];
        if ($financeTransaction->isTransfer() && $financeTransaction->transferPair) {
            $pair = $financeTransaction->transferPair;
            $data['from_wallet_id'] = $financeTransaction->type === 'transfer_out'
                ? $financeTransaction->wallet_id
                : $pair->wallet_id;
            $data['to_wallet_id'] = $financeTransaction->type === 'transfer_in'
                ? $financeTransaction->wallet_id
                : $pair->wallet_id;
        }
        return response()->json($data);
    }

    public function update(Request $request, FinanceTransaction $financeTransaction): JsonResponse
    {
        if (!auth()->user()->isAdmin() && $financeTransaction->user_id !== auth()->id()) {
            return response()->json([], 403);
        }
        if ($financeTransaction->isTransfer()) {
            return response()->json(['message' => 'Use transfer update endpoint'], 422);
        }
        $validated = $request->validate([
            'wallet_id' => 'required|exists:finance_wallets,id',
            'category_id' => 'required|exists:finance_categories,id',
            'amount' => ['required', 'numeric', 'min:1'],
            'description' => 'nullable|string|max:1000',
            'transaction_date' => 'required|date',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
        ]);
        $wallet = FinanceWallet::findOrFail($validated['wallet_id']);
        $category = FinanceCategory::findOrFail($validated['category_id']);
        $validated['type'] = $category->type;
        $oldWallet = $financeTransaction->wallet;
        $oldType = $financeTransaction->type;
        $oldAmount = (float) $financeTransaction->amount;
        $oldValues = [
            'wallet' => $oldWallet->name ?? 'Unknown',
            'category' => $financeTransaction->category->name ?? 'Unknown',
            'amount' => $oldAmount,
            'transaction_date' => $financeTransaction->getRawOriginal('transaction_date'),
        ];
        $financeTransaction->fill($validated);
        if (!$financeTransaction->isDirty() && !$request->has('tags')) {
            return response()->json([], 204);
        }
        DB::transaction(function () use ($financeTransaction, $validated, $wallet, $oldWallet, $oldType, $oldAmount, $request) {
            if ($oldWallet) {
                if ($oldType === 'income') {
                    $oldWallet->decrement('current_balance', $oldAmount);
                } else {
                    $oldWallet->increment('current_balance', $oldAmount);
                }
            }
            $financeTransaction->save();
            if ($validated['type'] === 'income') {
                $wallet->increment('current_balance', $validated['amount']);
            } else {
                $wallet->decrement('current_balance', $validated['amount']);
            }
            if ($request->has('tags')) {
                $tagIds = collect($request->tags)->map(function ($tagName) {
                    return FinanceTag::firstOrCreate(
                        ['name' => trim($tagName)],
                        ['user_id' => auth()->id(), 'color' => '#696CFF']
                    )->id;
                });
                $financeTransaction->tags()->sync($tagIds);
            }
        });
        AuditLog::record('transaction_updated', null, $oldValues, [
            'wallet' => $wallet->name,
            'category' => $category->name,
            'amount' => $financeTransaction->amount,
            'transaction_date' => $validated['transaction_date'],
        ]);
        return response()->json([], 200);
    }

    public function updateTransfer(Request $request, FinanceTransaction $financeTransaction): JsonResponse
    {
        if (!auth()->user()->isAdmin() && $financeTransaction->user_id !== auth()->id()) {
            return response()->json([], 403);
        }
        if (!$financeTransaction->isTransfer() || !$financeTransaction->transferPair) {
            return response()->json(['message' => 'Not a transfer'], 422);
        }
        $validated = $request->validate([
            'from_wallet_id' => 'required|exists:finance_wallets,id',
            'to_wallet_id' => 'required|exists:finance_wallets,id|different:from_wallet_id',
            'amount' => ['required', 'numeric', 'min:1'],
            'description' => 'nullable|string|max:1000',
            'transaction_date' => 'required|date',
        ]);
        $outTx = $financeTransaction->type === 'transfer_out' ? $financeTransaction : $financeTransaction->transferPair;
        $inTx = $financeTransaction->type === 'transfer_in' ? $financeTransaction : $financeTransaction->transferPair;
        $oldFromWallet = $outTx->wallet;
        $oldToWallet = $inTx->wallet;
        $oldAmount = (float) $outTx->amount;
        $outTx->fill([
            'wallet_id' => $validated['from_wallet_id'],
            'amount' => $validated['amount'],
            'description' => $validated['description'],
            'transaction_date' => $validated['transaction_date'],
        ]);
        $inTx->fill([
            'wallet_id' => $validated['to_wallet_id'],
            'amount' => $validated['amount'],
            'description' => $validated['description'],
            'transaction_date' => $validated['transaction_date'],
        ]);
        if (!$outTx->isDirty() && !$inTx->isDirty()) {
            return response()->json([], 204);
        }
        DB::transaction(function () use ($outTx, $inTx, $validated, $oldFromWallet, $oldToWallet, $oldAmount) {
            if ($oldFromWallet) {
                $oldFromWallet->increment('current_balance', $oldAmount);
            }
            if ($oldToWallet) {
                $oldToWallet->decrement('current_balance', $oldAmount);
            }
            $outTx->save();
            $inTx->save();
            $fromWallet = FinanceWallet::findOrFail($validated['from_wallet_id']);
            $toWallet = FinanceWallet::findOrFail($validated['to_wallet_id']);
            $fromWallet->decrement('current_balance', $validated['amount']);
            $toWallet->increment('current_balance', $validated['amount']);
        });
        AuditLog::record('transfer_updated', null, [
            'from_wallet' => $oldFromWallet->name ?? 'Unknown',
            'to_wallet' => $oldToWallet->name ?? 'Unknown',
            'amount' => $oldAmount,
        ], [
            'from_wallet' => FinanceWallet::find($validated['from_wallet_id'])->name,
            'to_wallet' => FinanceWallet::find($validated['to_wallet_id'])->name,
            'amount' => $validated['amount'],
        ]);
        return response()->json([], 200);
    }

    public function destroy(FinanceTransaction $financeTransaction): JsonResponse
    {
        if (!auth()->user()->isAdmin() && $financeTransaction->user_id !== auth()->id()) {
            return response()->json([], 403);
        }
        DB::transaction(function () use ($financeTransaction) {
            if ($financeTransaction->isTransfer() && $financeTransaction->transferPair) {
                $pair = $financeTransaction->transferPair;
                $outTx = $financeTransaction->type === 'transfer_out' ? $financeTransaction : $pair;
                $inTx = $financeTransaction->type === 'transfer_in' ? $financeTransaction : $pair;
                FinanceWallet::where('id', $outTx->wallet_id)->increment('current_balance', $outTx->amount);
                FinanceWallet::where('id', $inTx->wallet_id)->decrement('current_balance', $inTx->amount);
                AuditLog::record('transfer_deleted', null, [
                    'from_wallet' => $outTx->wallet->name ?? 'Unknown',
                    'to_wallet' => $inTx->wallet->name ?? 'Unknown',
                    'amount' => $outTx->amount,
                ], null);
                $pair->update(['transfer_pair_id' => null]);
                $financeTransaction->update(['transfer_pair_id' => null]);
                $pair->delete();
                $financeTransaction->delete();
            } else {
                $wallet = FinanceWallet::find($financeTransaction->wallet_id);
                if ($wallet) {
                    if ($financeTransaction->type === 'income') {
                        $wallet->decrement('current_balance', $financeTransaction->amount);
                    } else {
                        $wallet->increment('current_balance', $financeTransaction->amount);
                    }
                }
                AuditLog::record('transaction_deleted', null, [
                    'wallet' => $wallet->name ?? 'Unknown',
                    'category' => $financeTransaction->category->name ?? 'Unknown',
                    'type' => $financeTransaction->type,
                    'amount' => $financeTransaction->amount,
                ], null);
                $financeTransaction->delete();
            }
        });
        return response()->json([], 200);
    }
}