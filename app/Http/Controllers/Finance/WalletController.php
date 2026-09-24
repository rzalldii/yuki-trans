<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreWalletRequest;
use App\Http\Requests\Finance\UpdateWalletRequest;
use App\Http\Resources\Finance\WalletResource;
use App\Models\Finance\Wallet;
use App\Services\Finance\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class WalletController extends Controller
{
    public function __construct(
        protected WalletService $walletService
    ) {}

    public function index(): RedirectResponse
    {
        return redirect()->route('finance-master-data.index', ['tab' => 'wallets']);
    }

    public function store(StoreWalletRequest $request): JsonResponse
    {
        Gate::authorize('create', Wallet::class);
        $wallet = $this->walletService->createWallet($request->validated());
        return response()->json([
            'success' => true,
            'data' => new WalletResource($wallet),
        ], 201);
    }

    public function edit(Wallet $financeWallet): JsonResponse
    {
        Gate::authorize('view', $financeWallet);
        $hasTransactions = $financeWallet->transactions()->exists();
        return response()->json(array_merge(
            (new WalletResource($financeWallet))->resolve(),
            ['has_transactions' => $hasTransactions]
        ));
    }

    public function update(UpdateWalletRequest $request, Wallet $financeWallet): JsonResponse
    {
        Gate::authorize('update', $financeWallet);
        $updated = $this->walletService->updateWallet($financeWallet, $request->validated());
        if ($updated === null) {
            return response()->json([], 204);
        }
        return response()->json([
            'success' => true,
            'data' => new WalletResource($updated),
        ], 200);
    }

    public function destroy(Wallet $financeWallet): JsonResponse
    {
        Gate::authorize('delete', $financeWallet);
        if (!$this->walletService->deleteWallet($financeWallet)) {
            return response()->json(['success' => false], 422);
        }
        return response()->json(['success' => true], 200);
    }
}