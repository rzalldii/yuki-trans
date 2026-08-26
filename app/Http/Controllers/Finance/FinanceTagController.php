<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Finance\FinanceTag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class FinanceTagController extends Controller
{
    public function index()
    {
        return redirect()->route('finance-settings.index', ['tab' => 'tags']);
    }

    public function store(Request $request): JsonResponse
    {
        $request->merge([
            'name' => is_string($request->name) ? trim($request->name) : $request->name,
            'color' => is_string($request->color) ? trim($request->color) : $request->color,
        ]);
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('finance_tags')->where(function ($query) {
                    return $query->whereNull('deleted_at');
                })
            ],
            'color' => ['nullable', 'string', 'regex:/^#[a-fA-F0-9]{6}$/'],
        ]);
        $validated['color'] = !empty($validated['color']) ? strtolower($validated['color']) : '#696cff';
        DB::transaction(function () use ($validated) {
            $tag = FinanceTag::create($validated);
            AuditLog::record('tag_created', null, null, [
                'name' => $tag->name,
                'color' => $tag->color,
            ]);
        });
        return response()->json([], 201);
    }

    public function edit(FinanceTag $financeTag): JsonResponse
    {
        $hasTransactions = $financeTag->transactions()->exists();
        return response()->json([
            'id' => $financeTag->id,
            'name' => $financeTag->name,
            'color' => $financeTag->color,
            'has_transactions' => $hasTransactions,
        ]);
    }

    public function update(Request $request, FinanceTag $financeTag): JsonResponse
    {
        $request->merge([
            'name' => is_string($request->name) ? trim($request->name) : $request->name,
            'color' => is_string($request->color) ? trim($request->color) : $request->color,
        ]);
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('finance_tags')->ignore($financeTag->id)->where(function ($query) {
                    return $query->whereNull('deleted_at');
                })
            ],
            'color' => ['nullable', 'string', 'regex:/^#[a-fA-F0-9]{6}$/'],
        ]);
        $validated['color'] = !empty($validated['color']) ? strtolower($validated['color']) : ($financeTag->color ? strtolower($financeTag->color) : '#696cff');
        $oldValues = [
            'name' => $financeTag->name,
            'color' => $financeTag->color,
        ];
        $financeTag->fill($validated);
        if (!$financeTag->isDirty()) {
            return response()->json([], 204);
        }
        DB::transaction(function () use ($financeTag, $oldValues) {
            $financeTag->save();
            $newValues = [
                'name' => $financeTag->name,
                'color' => $financeTag->color,
            ];
            AuditLog::record('tag_updated', null, $oldValues, $newValues);
        });
        return response()->json([], 200);
    }

    public function destroy(FinanceTag $financeTag): JsonResponse
    {
        if ($financeTag->transactions()->exists()) {
            return response()->json([], 422);
        }

        $deletedInfo = [
            'name' => $financeTag->name,
            'color' => $financeTag->color,
        ];
        DB::transaction(function () use ($financeTag, $deletedInfo) {
            AuditLog::record('tag_deleted', null, $deletedInfo, null);
            $financeTag->delete();
        });
        return response()->json([], 200);
    }
}