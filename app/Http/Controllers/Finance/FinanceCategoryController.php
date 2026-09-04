<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Finance\FinanceCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class FinanceCategoryController extends Controller
{
    public function index()
    {
        return redirect()->route('finance-master-data.index', ['tab' => 'categories']);
    }

    public function store(Request $request): JsonResponse
    {
        $request->merge([
            'name' => is_string($request->name) ? trim($request->name) : $request->name,
        ]);
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
            'amount' => 'nullable|numeric|min:0',
        ]);
        DB::transaction(function () use ($validated) {
            $category = FinanceCategory::create($validated);
            AuditLog::record('category_created', null, null, [
                'name' => $category->name,
                'type' => $category->type,
                'amount' => $category->amount,
            ]);
        });
        return response()->json(['success' => true], 201);
    }

    public function edit(FinanceCategory $financeCategory): JsonResponse
    {
        $hasTransactions = $financeCategory->transactions()->exists() || $financeCategory->recurrings()->exists();
        return response()->json([
            'id' => $financeCategory->id,
            'name' => $financeCategory->name,
            'type' => $financeCategory->type,
            'amount' => $financeCategory->amount,
            'has_transactions' => $hasTransactions,
        ]);
    }

    public function update(Request $request, FinanceCategory $financeCategory): JsonResponse
    {
        $hasTransactions = $financeCategory->transactions()->exists() || $financeCategory->recurrings()->exists();
        $mergeData = [
            'name' => is_string($request->name) ? trim($request->name) : $request->name,
        ];
        if ($hasTransactions) {
            $mergeData['type'] = $financeCategory->type;
        }
        $request->merge($mergeData);
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('finance_categories')->ignore($financeCategory->id)->where(function ($query) use ($request, $financeCategory, $hasTransactions) {
                    $type = $hasTransactions ? $financeCategory->type : $request->type;
                    return $query->where('type', $type)->whereNull('deleted_at');
                })
            ],
            'type' => 'required|in:income,expense',
            'amount' => 'nullable|numeric|min:0',
        ]);
        $oldValues = [
            'name' => $financeCategory->name,
            'type' => $financeCategory->type,
            'amount' => $financeCategory->amount,
        ];
        $financeCategory->fill($validated);
        if (!$financeCategory->isDirty()) {
            return response()->json([], 204);
        }
        DB::transaction(function () use ($financeCategory, $oldValues) {
            $financeCategory->save();
            $newValues = [
                'name' => $financeCategory->name,
                'type' => $financeCategory->type,
                'amount' => $financeCategory->amount,
            ];
            AuditLog::record('category_updated', null, $oldValues, $newValues);
        });
        return response()->json(['success' => true], 200);
    }

    public function destroy(FinanceCategory $financeCategory): JsonResponse
    {
        if ($financeCategory->transactions()->exists() || $financeCategory->recurrings()->exists()) {
            return response()->json(['success' => false], 422);
        }
        $deletedInfo = [
            'name' => $financeCategory->name,
            'type' => $financeCategory->type,
            'amount' => $financeCategory->amount,
        ];
        DB::transaction(function () use ($financeCategory, $deletedInfo) {
            AuditLog::record('category_deleted', null, $deletedInfo, null);
            $financeCategory->delete();
        });
        return response()->json(['success' => true], 200);
    }
}