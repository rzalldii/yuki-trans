<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Enums\Finance\TransactionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreTransactionRequest;
use App\Http\Requests\Finance\StoreTransferRequest;
use App\Http\Requests\Finance\UpdateTransactionRequest;
use App\Http\Requests\Finance\UpdateTransferRequest;
use App\Http\Resources\Finance\TransactionResource;
use App\Models\Finance\Transaction;
use App\Services\Finance\TransactionQueryService;
use App\Services\Finance\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function __construct(
        protected TransactionService $transactionService,
        protected TransactionQueryService $queryService
    ) {}

    public function index(Request $request): View
    {
        return view('pages.finance.transactions', $this->queryService->getIndexData($request));
    }

    public function store(StoreTransactionRequest $request): JsonResponse
    {
        Gate::authorize('create', Transaction::class);
        $validated = $request->validated();
        $tags = $validated['tags'] ?? null;
        $transaction = $this->transactionService->createTransaction($validated, $tags, auth()->id());
        $transaction->load(['wallet', 'category', 'transferPair.wallet', 'tags']);
        return response()->json([
            'success' => true,
            'data' => new TransactionResource($transaction),
        ], 201);
    }

    public function storeTransfer(StoreTransferRequest $request): JsonResponse
    {
        Gate::authorize('createTransfer', Transaction::class);
        $validated = $request->validated();
        $tags = $validated['tags'] ?? null;
        $pair = $this->transactionService->createTransfer($validated, $tags, auth()->id());
        $outTx = $pair['out']->load(['wallet', 'category', 'transferPair.wallet', 'tags']);
        return response()->json([
            'success' => true,
            'data' => new TransactionResource($outTx),
        ], 201);
    }

    public function edit(Transaction $financeTransaction): JsonResponse
    {
        Gate::authorize('view', $financeTransaction);
        $extra = [
            'tags' => $financeTransaction->tags->pluck('name'),
        ];
        if ($financeTransaction->isTransfer() && $financeTransaction->transferPair) {
            $pair = $financeTransaction->transferPair;
            $isTransferOut = $financeTransaction->type === TransactionType::TransferOut;
            $isTransferIn = $financeTransaction->type === TransactionType::TransferIn;
            $extra['from_wallet_id'] = $isTransferOut
                ? $financeTransaction->wallet_id
                : $pair->wallet_id;
            $extra['to_wallet_id'] = $isTransferIn
                ? $financeTransaction->wallet_id
                : $pair->wallet_id;
        }
        return response()->json(array_merge(
            (new TransactionResource($financeTransaction))->resolve(),
            $extra
        ));
    }

    public function update(UpdateTransactionRequest $request, Transaction $financeTransaction): JsonResponse
    {
        Gate::authorize('update', $financeTransaction);
        if ($financeTransaction->isTransfer()) {
            return response()->json(['success' => false], 422);
        }
        $validated = $request->validated();
        $tags = $validated['tags'] ?? null;
        $updated = $this->transactionService->updateTransaction($financeTransaction, $validated, $tags);
        if ($updated === null) {
            return response()->json([], 204);
        }
        $updated->load(['wallet', 'category', 'transferPair.wallet', 'tags']);
        return response()->json([
            'success' => true,
            'data' => new TransactionResource($updated),
        ], 200);
    }

    public function updateTransfer(UpdateTransferRequest $request, Transaction $financeTransaction): JsonResponse
    {
        Gate::authorize('update', $financeTransaction);
        if (!$financeTransaction->isTransfer() || !$financeTransaction->transferPair) {
            return response()->json(['success' => false], 422);
        }
        $validated = $request->validated();
        $tags = $validated['tags'] ?? null;
        $result = $this->transactionService->updateTransfer($financeTransaction, $validated, $tags);
        if ($result === null) {
            return response()->json([], 204);
        }
        $outTx = $result['out']->load(['wallet', 'category', 'transferPair.wallet', 'tags']);
        return response()->json([
            'success' => true,
            'data' => new TransactionResource($outTx),
        ], 200);
    }

    public function destroy(Transaction $financeTransaction): JsonResponse
    {
        Gate::authorize('delete', $financeTransaction);
        $this->transactionService->deleteTransaction($financeTransaction);
        return response()->json(['success' => true], 200);
    }
}