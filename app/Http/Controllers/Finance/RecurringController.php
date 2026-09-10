<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Enums\Frequency;
use App\Enums\RecurringType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreRecurringRequest;
use App\Http\Requests\Finance\UpdateRecurringRequest;
use App\Models\Finance\Recurring;
use App\Services\Finance\RecurringExecutionService;
use App\Services\Finance\RecurringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class RecurringController extends Controller
{
    public function index()
    {
        return redirect()->route('finance-master-data.index', ['tab' => 'recurring']);
    }

    public function store(StoreRecurringRequest $request, RecurringService $service): JsonResponse
    {
        Gate::authorize('create', Recurring::class);
        $service->createRecurring($request->validated());
        return response()->json(['success' => true], 201);
    }

    public function edit(Recurring $financeRecurring): JsonResponse
    {
        Gate::authorize('view', $financeRecurring);
        $hasTransactions = $financeRecurring->generatedTransactions()->exists();
        $typeVal = $financeRecurring->type instanceof RecurringType ? $financeRecurring->type->value : $financeRecurring->type;
        $freqVal = $financeRecurring->frequency instanceof Frequency ? $financeRecurring->frequency->value : $financeRecurring->frequency;
        return response()->json([
            'id' => $financeRecurring->id,
            'type' => $typeVal,
            'wallet_id' => $financeRecurring->wallet_id,
            'to_wallet_id' => $financeRecurring->to_wallet_id,
            'category_id' => $financeRecurring->category_id,
            'amount' => (int) $financeRecurring->amount,
            'description' => $financeRecurring->description,
            'frequency' => $freqVal,
            'start_date' => $financeRecurring->start_date ? $financeRecurring->start_date->format('Y-m-d') : null,
            'end_date' => $financeRecurring->end_date ? $financeRecurring->end_date->format('Y-m-d') : null,
            'is_active' => $financeRecurring->is_active,
            'tags' => $financeRecurring->tags->pluck('name'),
            'last_generated_at' => $financeRecurring->last_generated_at ? $financeRecurring->last_generated_at->format('Y-m-d') : null,
            'has_transactions' => $hasTransactions,
        ]);
    }

    public function update(UpdateRecurringRequest $request, Recurring $financeRecurring, RecurringService $service): JsonResponse
    {
        Gate::authorize('update', $financeRecurring);
        $updated = $service->updateRecurring($financeRecurring, $request->validated(), $request->has('tags'));
        if (!$updated) {
            return response()->json([], 204);
        }
        return response()->json(['success' => true], 200);
    }

    public function destroy(Request $request, Recurring $financeRecurring, RecurringService $service): JsonResponse
    {
        Gate::authorize('delete', $financeRecurring);
        $service->deleteRecurring($financeRecurring, $request->boolean('delete_transactions'));
        return response()->json(['success' => true], 200);
    }

    public function toggleStatus(Recurring $financeRecurring, RecurringService $service): JsonResponse
    {
        Gate::authorize('update', $financeRecurring);
        $service->toggleStatus($financeRecurring);
        return response()->json(['success' => true], 200);
    }

    public function generate(RecurringExecutionService $service): JsonResponse
    {
        Gate::authorize('create', Recurring::class);
        $generated = $service->processDueRecurrings(auth()->id());
        return response()->json([
            'success' => true,
            'generated' => $generated,
        ], 200);
    }
}