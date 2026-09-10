<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreWalletRequest;
use App\Http\Requests\Finance\UpdateWalletRequest;
use App\Models\Finance\Wallet;
use App\Services\Finance\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class WalletController extends Controller
{
    public function index()
    {
        return redirect()->route('finance-master-data.index', ['tab' => 'wallets']);
    }

    public function store(StoreWalletRequest $request, WalletService $service): JsonResponse
    {
        Gate::authorize('create', Wallet::class);
        $service->createWallet($request->validated());
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

    public function update(UpdateWalletRequest $request, Wallet $financeWallet, WalletService $service): JsonResponse
    {
        Gate::authorize('update', $financeWallet);
        $updated = $service->updateWallet($financeWallet, $request->validated());
        if ($updated === null) {
            return response()->json([], 204);
        }
        return response()->json(['success' => true], 200);
    }

    public function destroy(Wallet $financeWallet, WalletService $service): JsonResponse
    {
        Gate::authorize('delete', $financeWallet);
        if (!$service->deleteWallet($financeWallet)) {
            return response()->json(['success' => false], 422);
        }
        return response()->json(['success' => true], 200);
    }
}