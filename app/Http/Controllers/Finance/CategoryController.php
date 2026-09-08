<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreCategoryRequest;
use App\Http\Requests\Finance\UpdateCategoryRequest;
use App\Models\Audit\AuditLog;
use App\Models\Finance\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class CategoryController extends Controller
{
    public function index()
    {
        return redirect()->route('finance-master-data.index', ['tab' => 'categories']);
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        Gate::authorize('create', Category::class);
        $validated = $request->validated();
        DB::transaction(function () use ($validated) {
            $category = Category::create($validated);
            AuditLog::record('category_created', null, null, [
                'name' => $category->name,
                'type' => $category->type,
                'amount' => $category->amount,
            ]);
        });
        return response()->json(['success' => true], 201);
    }

    public function edit(Category $financeCategory): JsonResponse
    {
        Gate::authorize('view', $financeCategory);
        $hasTransactions = $financeCategory->transactions()->exists() || $financeCategory->recurrings()->exists();
        return response()->json([
            'id' => $financeCategory->id,
            'name' => $financeCategory->name,
            'type' => $financeCategory->type,
            'amount' => $financeCategory->amount,
            'has_transactions' => $hasTransactions,
        ]);
    }

    public function update(UpdateCategoryRequest $request, Category $financeCategory): JsonResponse
    {
        Gate::authorize('update', $financeCategory);
        $validated = $request->validated();
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

    public function destroy(Category $financeCategory): JsonResponse
    {
        Gate::authorize('delete', $financeCategory);
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