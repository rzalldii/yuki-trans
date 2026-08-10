<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Finance\FinanceWallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinanceWalletController extends Controller
{
    public function index()
    {
        $wallets = FinanceWallet::orderBy('name')->get();
        return view('pages.finance.finance-wallet', compact('wallets'));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:finance_wallets,name',
            'initial_balance' => ['required', 'string', 'regex:/^\d+$/'],
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
            'name' => 'required|string|max:255|unique:finance_wallets,name,' . $financeWallet->id,
            'initial_balance' => ['required', 'string', 'regex:/^\d+$/'],
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
        $deletedInfo = [
            'name' => $financeWallet->name,
            'initial_balance' => $financeWallet->initial_balance,
        ];
        AuditLog::record('wallet_deleted', null, $deletedInfo, null);
        $financeWallet->delete();
        return response()->json([], 200);
    }
}