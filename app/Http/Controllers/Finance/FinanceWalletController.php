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
        $wallets = FinanceWallet::withSum(['transactions as income_sum' => function ($query) {
            $query->whereHas('category', function ($q) {
                $q->where('type', 'income');
            });
        }], 'amount')
        ->withSum(['transactions as expense_sum' => function ($query) {
            $query->whereHas('category', function ($q) {
                $q->where('type', 'expense');
            });
        }], 'amount')
        ->orderBy('name')->get();

        return view('pages.finance.finance-wallet', compact('wallets'));
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
        $wallet = FinanceWallet::create($validated);
        AuditLog::record('wallet_created', null, null, [
            'name' => $wallet->name,
            'initial_balance' => $wallet->initial_balance,
        ]);
        return response()->json([], 201);
    }

    public function edit(FinanceWallet $financeWallet): JsonResponse
    {
        return response()->json($financeWallet->only(['id', 'name', 'initial_balance']));
    }

    public function update(Request $request, FinanceWallet $financeWallet): JsonResponse
    {
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