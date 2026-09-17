<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreCategoryRequest;
use App\Http\Requests\Finance\UpdateCategoryRequest;
use App\Http\Resources\Finance\CategoryResource;
use App\Models\Finance\Category;
use App\Services\Finance\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class CategoryController extends Controller
{
    public function __construct(
        protected CategoryService $categoryService
    ) {}

    public function index(): RedirectResponse
    {
        return redirect()->route('finance-master-data.index', ['tab' => 'categories']);
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        Gate::authorize('create', Category::class);
        $category = $this->categoryService->createCategory($request->validated());
        return response()->json([
            'success' => true,
            'data' => new CategoryResource($category),
        ], 201);
    }

    public function edit(Category $financeCategory): JsonResponse
    {
        Gate::authorize('view', $financeCategory);
        $hasTransactions = $financeCategory->transactions()->exists() || $financeCategory->recurrings()->exists();
        return response()->json([
            'id' => $financeCategory->id,
            'name' => $financeCategory->name,
            'type' => $financeCategory->typeValue(),
            'amount' => $financeCategory->amount,
            'has_transactions' => $hasTransactions,
        ]);
    }

    public function update(UpdateCategoryRequest $request, Category $financeCategory): JsonResponse
    {
        Gate::authorize('update', $financeCategory);
        $updated = $this->categoryService->updateCategory($financeCategory, $request->validated());
        if ($updated === null) {
            return response()->json([], 204);
        }
        return response()->json([
            'success' => true,
            'data' => new CategoryResource($updated),
        ], 200);
    }

    public function destroy(Category $financeCategory): JsonResponse
    {
        Gate::authorize('delete', $financeCategory);
        if (!$this->categoryService->deleteCategory($financeCategory)) {
            return response()->json(['success' => false], 422);
        }
        return response()->json(['success' => true], 200);
    }
}