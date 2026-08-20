<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Finance\FinanceCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FinanceCategoryController extends Controller
{
    public function index()
    {
        return redirect()->route('finance-settings.index', ['tab' => 'categories']);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('finance_categories')->where(function ($query) use ($request) {
                    return $query->where('type', $request->type)->whereNull('deleted_at');
                })
            ],
            'type' => 'required|in:income,expense',
            'budget' => 'nullable|numeric|min:0',
        ]);
        $category = FinanceCategory::create($validated);
        AuditLog::record('category_created', null, null, [
            'name' => $category->name,
            'type' => $category->type,
            'budget' => $category->budget,
        ]);
        return response()->json([], 201);
    }

    public function edit(FinanceCategory $financeCategory): JsonResponse
    {
        return response()->json($financeCategory->only(['id', 'name', 'type', 'budget']));
    }

    public function update(Request $request, FinanceCategory $financeCategory): JsonResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('finance_categories')->ignore($financeCategory->id)->where(function ($query) use ($request) {
                    return $query->where('type', $request->type)->whereNull('deleted_at');
                })
            ],
            'type' => 'required|in:income,expense',
            'budget' => 'nullable|numeric|min:0',
        ]);
        $oldValues = [
            'name' => $financeCategory->name,
            'type' => $financeCategory->type,
            'budget' => $financeCategory->budget,
        ];
        $financeCategory->fill($validated);
        if (!$financeCategory->isDirty()) {
            return response()->json([], 204);
        }
        $financeCategory->save();
        $newValues = [
            'name' => $financeCategory->name,
            'type' => $financeCategory->type,
            'budget' => $financeCategory->budget,
        ];
        AuditLog::record('category_updated', null, $oldValues, $newValues);
        return response()->json([], 200);
    }

    public function destroy(FinanceCategory $financeCategory): JsonResponse
    {
        if ($financeCategory->transactions()->exists() || $financeCategory->recurringTransactions()->exists()) {
            return response()->json([], 422);
        }
        $deletedInfo = [
            'name' => $financeCategory->name,
            'type' => $financeCategory->type,
            'budget' => $financeCategory->budget,
        ];
        AuditLog::record('category_deleted', null, $deletedInfo, null);
        $financeCategory->delete();
        return response()->json([], 200);
    }
}