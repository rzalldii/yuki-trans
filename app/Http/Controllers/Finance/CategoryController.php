<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreCategoryRequest;
use App\Http\Requests\Finance\UpdateCategoryRequest;
use App\Models\Finance\Category;
use App\Services\Finance\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class CategoryController extends Controller
{
    public function index()
    {
        return redirect()->route('finance-master-data.index', ['tab' => 'categories']);
    }

    public function store(StoreCategoryRequest $request, CategoryService $service): JsonResponse
    {
        Gate::authorize('create', Category::class);
        $service->createCategory($request->validated());
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

    public function update(UpdateCategoryRequest $request, Category $financeCategory, CategoryService $service): JsonResponse
    {
        Gate::authorize('update', $financeCategory);
        $updated = $service->updateCategory($financeCategory, $request->validated());
        if ($updated === null) {
            return response()->json([], 204);
        }
        return response()->json(['success' => true], 200);
    }

    public function destroy(Category $financeCategory, CategoryService $service): JsonResponse
    {
        Gate::authorize('delete', $financeCategory);
        if (!$service->deleteCategory($financeCategory)) {
            return response()->json(['success' => false], 422);
        }
        return response()->json(['success' => true], 200);
    }
}