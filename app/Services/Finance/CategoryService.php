<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Models\Audit\AuditLog;
use App\Models\Finance\Category;
use Illuminate\Support\Facades\DB;

class CategoryService
{
    public function createCategory(array $validated): Category
    {
        return DB::transaction(function () use ($validated) {
            $category = Category::create($validated);
            AuditLog::record('category_created', null, null, [
                'name' => $category->name,
                'type' => $category->type,
                'amount' => $category->amount,
            ]);
            return $category;
        });
    }

    public function updateCategory(Category $category, array $validated): ?Category
    {
        $oldValues = [
            'name' => $category->name,
            'type' => $category->type,
            'amount' => $category->amount,
        ];
        $category->fill($validated);
        if (!$category->isDirty()) {
            return null;
        }
        return DB::transaction(function () use ($category, $oldValues) {
            $category->save();
            $newValues = [
                'name' => $category->name,
                'type' => $category->type,
                'amount' => $category->amount,
            ];
            AuditLog::record('category_updated', null, $oldValues, $newValues);
            return $category;
        });
    }

    public function deleteCategory(Category $category): bool
    {
        if ($category->transactions()->exists() || $category->recurrings()->exists()) {
            return false;
        }
        $deletedInfo = [
            'name' => $category->name,
            'type' => $category->type,
            'amount' => $category->amount,
        ];
        DB::transaction(function () use ($category, $deletedInfo) {
            AuditLog::record('category_deleted', null, $deletedInfo, null);
            $category->delete();
        });
        return true;
    }
}