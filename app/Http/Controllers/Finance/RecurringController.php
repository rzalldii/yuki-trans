<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreRecurringRequest;
use App\Http\Requests\Finance\UpdateRecurringRequest;
use App\Http\Resources\Finance\RecurringResource;
use App\Models\Finance\Recurring;
use App\Services\Finance\RecurringExecutionService;
use App\Services\Finance\RecurringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class RecurringController extends Controller
{
    public function __construct(
        protected RecurringService $recurringService,
        protected RecurringExecutionService $executionService
    ) {}

    public function index(): RedirectResponse
    {
        return redirect()->route('finance-master-data.index', ['tab' => 'recurring']);
    }

    public function store(StoreRecurringRequest $request): JsonResponse
    {
        Gate::authorize('create', Recurring::class);
        $recurring = $this->recurringService->createRecurring($request->validated());
        $recurring->load(['wallet', 'toWallet', 'category']);
        return response()->json([
            'success' => true,
            'data' => new RecurringResource($recurring),
        ], 201);
    }

    public function edit(Recurring $financeRecurring): JsonResponse
    {
        Gate::authorize('view', $financeRecurring);
        $hasTransactions = $financeRecurring->generatedTransactions()->exists();
        return response()->json([
            'id' => $financeRecurring->id,
            'type' => $financeRecurring->typeValue(),
            'wallet_id' => $financeRecurring->wallet_id,
            'to_wallet_id' => $financeRecurring->to_wallet_id,
            'category_id' => $financeRecurring->category_id,
            'amount' => (int) $financeRecurring->amount,
            'description' => $financeRecurring->description,
            'frequency' => $financeRecurring->frequencyValue(),
            'start_date' => $financeRecurring->start_date ? $financeRecurring->start_date->format('Y-m-d') : null,
            'end_date' => $financeRecurring->end_date ? $financeRecurring->end_date->format('Y-m-d') : null,
            'is_active' => $financeRecurring->is_active,
            'tags' => $financeRecurring->tags->pluck('name'),
            'last_generated_at' => $financeRecurring->last_generated_at ? $financeRecurring->last_generated_at->format('Y-m-d') : null,
            'has_transactions' => $hasTransactions,
        ]);
    }

    public function update(UpdateRecurringRequest $request, Recurring $financeRecurring): JsonResponse
    {
        Gate::authorize('update', $financeRecurring);
        $updated = $this->recurringService->updateRecurring($financeRecurring, $request->validated(), $request->has('tags'));
        if ($updated === null) {
            return response()->json([], 204);
        }
        $updated->load(['wallet', 'toWallet', 'category']);
        return response()->json([
            'success' => true,
            'data' => new RecurringResource($updated),
        ], 200);
    }

    public function destroy(Request $request, Recurring $financeRecurring): JsonResponse
    {
        Gate::authorize('delete', $financeRecurring);
        $this->recurringService->deleteRecurring($financeRecurring, $request->boolean('delete_transactions'));
        return response()->json(['success' => true], 200);
    }

    public function toggleStatus(Recurring $financeRecurring): JsonResponse
    {
        Gate::authorize('update', $financeRecurring);
        $this->recurringService->toggleStatus($financeRecurring);
        return response()->json(['success' => true], 200);
    }

    public function generate(): JsonResponse
    {
        Gate::authorize('create', Recurring::class);
        $generated = $this->executionService->processDueRecurrings(auth()->id());
        return response()->json([
            'success' => true,
            'generated' => $generated,
        ], 200);
    }
}