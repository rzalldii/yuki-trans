<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Finance\FinanceWallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FinanceWalletController extends Controller
{
    public function index()
    {
        return redirect()->route('finance-settings.index', ['tab' => 'wallets']);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('finance_wallets')->where(function ($query) {
                    return $query->whereNull('deleted_at');
                })
            ],
            'initial_balance' => ['required', 'numeric', 'min:0'],
        ]);
        $validated['current_balance'] = $validated['initial_balance'];
        $wallet = FinanceWallet::create($validated);
        AuditLog::record('wallet_created', null, null, [
            'name' => $wallet->name,
            'initial_balance' => $wallet->initial_balance,
        ]);
        return response()->json([], 201);
    }

    public function edit(FinanceWallet $financeWallet): JsonResponse
    {
        $hasTransactions = $financeWallet->transactions()->exists();
        return response()->json([
            'id' => $financeWallet->id,
            'name' => $financeWallet->name,
            'initial_balance' => $financeWallet->initial_balance,
            'current_balance' => $financeWallet->current_balance,
            'has_transactions' => $hasTransactions,
        ]);
    }

    public function update(Request $request, FinanceWallet $financeWallet): JsonResponse
    {
        $hasTransactions = $financeWallet->transactions()->exists();
        if ($hasTransactions) {
            $request->merge(['initial_balance' => $financeWallet->initial_balance]);
        }
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('finance_wallets')->ignore($financeWallet->id)->where(function ($query) {
                    return $query->whereNull('deleted_at');
                })
            ],
            'initial_balance' => ['required', 'numeric', 'min:0'],
        ]);
        $oldValues = [
            'name' => $financeWallet->name,
            'initial_balance' => $financeWallet->initial_balance,
        ];
        if (!$hasTransactions) {
            $diff = (float) $validated['initial_balance'] - (float) $financeWallet->initial_balance;
            $validated['current_balance'] = (float) $financeWallet->current_balance + $diff;
        }
        $financeWallet->fill($validated);
        if (!$financeWallet->isDirty()) {
            return response()->json([], 204);
        }
        $financeWallet->save();
        $newValues = [
            'name' => $financeWallet->name,
            'initial_balance' => $financeWallet->initial_balance,
        ];
        AuditLog::record('wallet_updated', null, $oldValues, $newValues);
        return response()->json([], 200);
    }

    public function destroy(FinanceWallet $financeWallet): JsonResponse
    {
        if ($financeWallet->transactions()->exists()) {
            return response()->json([], 422);
        }
        $deletedInfo = [
            'name' => $financeWallet->name,
            'initial_balance' => $financeWallet->initial_balance,
        ];
        AuditLog::record('wallet_deleted', null, $deletedInfo, null);
        $financeWallet->delete();
        return response()->json([], 200);
    }
}