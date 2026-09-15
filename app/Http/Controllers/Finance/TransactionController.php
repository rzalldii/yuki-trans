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
use App\Models\Finance\Category;
use App\Models\Finance\Tag;
use App\Models\Finance\Transaction;
use App\Models\Finance\Wallet;
use App\Services\Finance\TransactionService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function index(Request $request): View
    {
        $categories = Category::orderBy('name')->get();
        $wallets = Wallet::orderBy('name')->get();
        $tags = Tag::orderBy('name')->get();
        $hasEntityFilter = $request->filled('wallet') || $request->filled('category') || $request->filled('tag');
        $startDate = (string) $request->input('start_date', $hasEntityFilter ? '2020-01-01' : now()->startOfMonth()->toDateString());
        $endDate = (string) $request->input('end_date', $hasEntityFilter ? now()->addYear()->toDateString() : now()->endOfMonth()->toDateString());
        $query = Transaction::with(['user', 'wallet', 'category', 'transferPair.wallet', 'tags'])
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->where('type', '!=', TransactionType::TransferIn->value)
            ->orderByDesc('transaction_date')
            ->orderByDesc('id');
        if ($request->has('page') || $request->boolean('paginate')) {
            $perPage = min((int) $request->input('per_page', 50), 100);
            $ledger = $query->paginate($perPage)->withQueryString();
        } else {
            $ledger = $query->take(1000)->get();
        }
        $version = Cache::get('finance_summary_version', 1);
        $summaryCacheKey = "finance_summary_{$startDate}_{$endDate}_v{$version}";
        $monthlySummary = Cache::remember($summaryCacheKey, 3600, function () use ($startDate, $endDate) {
            return Transaction::whereBetween('transaction_date', [$startDate, $endDate])
                ->selectRaw("
                    SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as total_income,
                    SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as total_expense
                ")
                ->first();
        });
        $totalIncome = (float) ($monthlySummary->total_income ?? 0);
        $totalExpense = (float) ($monthlySummary->total_expense ?? 0);
        $netBalance = (float) Cache::rememberForever('finance_net_balance', function () {
            return Wallet::sum('current_balance');
        });
        $filterCategories = $categories->pluck('name')->unique()->sort()->values();
        $filterTypes = collect(['income', 'expense', 'transfer']);
        $filterTags = $tags->pluck('name')->unique()->sort()->values();
        $currentMonthLabel = ($startDate === '2020-01-01' && !$request->has('start_date'))
            ? 'All Time'
            : Carbon::parse($startDate)->translatedFormat('d M Y') . ' - ' . Carbon::parse($endDate)->translatedFormat('d M Y');
        return view('pages.finance.transactions', compact(
            'wallets', 'categories', 'tags', 'ledger', 'filterCategories', 'filterTypes', 'filterTags',
            'totalIncome', 'totalExpense', 'netBalance', 'currentMonthLabel',
            'startDate', 'endDate'
        ));
    }

    public function store(StoreTransactionRequest $request, TransactionService $service): JsonResponse
    {
        Gate::authorize('create', Transaction::class);
        $validated = $request->validated();
        $tags = $validated['tags'] ?? null;
        $transaction = $service->createTransaction($validated, $tags, auth()->id());
        $transaction->load(['wallet', 'category', 'transferPair.wallet', 'tags']);
        return response()->json([
            'success' => true,
            'data' => new TransactionResource($transaction),
        ], 201);
    }

    public function storeTransfer(StoreTransferRequest $request, TransactionService $service): JsonResponse
    {
        Gate::authorize('createTransfer', Transaction::class);
        $validated = $request->validated();
        $tags = $validated['tags'] ?? null;
        $pair = $service->createTransfer($validated, $tags, auth()->id());
        $outTx = $pair['out']->load(['wallet', 'category', 'transferPair.wallet', 'tags']);
        return response()->json([
            'success' => true,
            'data' => new TransactionResource($outTx),
        ], 201);
    }

    public function edit(Transaction $financeTransaction): JsonResponse
    {
        Gate::authorize('view', $financeTransaction);
        $typeVal = $financeTransaction->type instanceof TransactionType ? $financeTransaction->type->value : $financeTransaction->type;
        $data = [
            'id' => $financeTransaction->id,
            'type' => $typeVal,
            'wallet_id' => $financeTransaction->wallet_id,
            'category_id' => $financeTransaction->category_id,
            'amount' => (int) $financeTransaction->amount,
            'description' => $financeTransaction->description,
            'transaction_date' => $financeTransaction->transaction_date ? $financeTransaction->transaction_date->format('Y-m-d') : null,
            'tags' => $financeTransaction->tags->pluck('name'),
        ];
        if ($financeTransaction->isTransfer() && $financeTransaction->transferPair) {
            $pair = $financeTransaction->transferPair;
            $isTransferOut = $financeTransaction->type === TransactionType::TransferOut || $financeTransaction->type === 'transfer_out';
            $isTransferIn = $financeTransaction->type === TransactionType::TransferIn || $financeTransaction->type === 'transfer_in';
            $data['from_wallet_id'] = $isTransferOut
                ? $financeTransaction->wallet_id
                : $pair->wallet_id;
            $data['to_wallet_id'] = $isTransferIn
                ? $financeTransaction->wallet_id
                : $pair->wallet_id;
        }
        return response()->json($data);
    }

    public function update(UpdateTransactionRequest $request, Transaction $financeTransaction, TransactionService $service): JsonResponse
    {
        Gate::authorize('update', $financeTransaction);
        if ($financeTransaction->isTransfer()) {
            return response()->json(['success' => false], 422);
        }
        $validated = $request->validated();
        $tags = $validated['tags'] ?? null;
        $updated = $service->updateTransaction($financeTransaction, $validated, $tags);
        if ($updated === null) {
            return response()->json([], 204);
        }
        $updated->load(['wallet', 'category', 'transferPair.wallet', 'tags']);
        return response()->json([
            'success' => true,
            'data' => new TransactionResource($updated),
        ], 200);
    }

    public function updateTransfer(UpdateTransferRequest $request, Transaction $financeTransaction, TransactionService $service): JsonResponse
    {
        Gate::authorize('update', $financeTransaction);
        if (!$financeTransaction->isTransfer() || !$financeTransaction->transferPair) {
            return response()->json(['success' => false], 422);
        }
        $validated = $request->validated();
        $tags = $validated['tags'] ?? null;
        $result = $service->updateTransfer($financeTransaction, $validated, $tags);
        if ($result === null) {
            return response()->json([], 204);
        }
        $outTx = $result['out']->load(['wallet', 'category', 'transferPair.wallet', 'tags']);
        return response()->json([
            'success' => true,
            'data' => new TransactionResource($outTx),
        ], 200);
    }

    public function destroy(Transaction $financeTransaction, TransactionService $service): JsonResponse
    {
        Gate::authorize('delete', $financeTransaction);
        $service->deleteTransaction($financeTransaction);
        return response()->json(['success' => true], 200);
    }
}