<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreWalletRequest;
use App\Http\Requests\Finance\UpdateWalletRequest;
use App\Models\Audit\AuditLog;
use App\Models\Finance\Wallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class WalletController extends Controller
{
    public function index()
    {
        return redirect()->route('finance-master-data.index', ['tab' => 'wallets']);
    }

    public function store(StoreWalletRequest $request): JsonResponse
    {
        Gate::authorize('create', Wallet::class);
        $validated = $request->validated();
        $validated['current_balance'] = $validated['initial_balance'];
        DB::transaction(function () use ($validated) {
            $wallet = Wallet::create($validated);
            AuditLog::record('wallet_created', null, null, [
                'name' => $wallet->name,
                'initial_balance' => $wallet->initial_balance,
            ]);
        });
        return response()->json(['success' => true], 201);
    }

    public function edit(Wallet $financeWallet): JsonResponse
    {
        Gate::authorize('view', $financeWallet);
        $hasTransactions = $financeWallet->transactions()->exists();
        return response()->json([
            'id' => $financeWallet->id,
            'name' => $financeWallet->name,
            'initial_balance' => $financeWallet->initial_balance,
            'current_balance' => $financeWallet->current_balance,
            'has_transactions' => $hasTransactions,
        ]);
    }

    public function update(UpdateWalletRequest $request, Wallet $financeWallet): JsonResponse
    {
        Gate::authorize('update', $financeWallet);
        $validated = $request->validated();
        $hasTransactions = $financeWallet->transactions()->exists();
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
        DB::transaction(function () use ($financeWallet, $oldValues) {
            $financeWallet->save();
            $newValues = [
                'name' => $financeWallet->name,
                'initial_balance' => $financeWallet->initial_balance,
            ];
            AuditLog::record('wallet_updated', null, $oldValues, $newValues);
        });
        return response()->json(['success' => true], 200);
    }

    public function destroy(Wallet $financeWallet): JsonResponse
    {
        Gate::authorize('delete', $financeWallet);
        if ($financeWallet->transactions()->exists() || $financeWallet->recurrings()->exists() || $financeWallet->toRecurrings()->exists()) {
            return response()->json(['success' => false], 422);
        }
        $deletedInfo = [
            'name' => $financeWallet->name,
            'initial_balance' => $financeWallet->initial_balance,
        ];
        DB::transaction(function () use ($financeWallet, $deletedInfo) {
            AuditLog::record('wallet_deleted', null, $deletedInfo, null);
            $financeWallet->delete();
        });
        return response()->json(['success' => true], 200);
    }
}