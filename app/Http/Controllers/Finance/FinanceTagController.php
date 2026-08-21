<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Finance\FinanceTag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FinanceTagController extends Controller
{
    public function index()
    {
        return redirect()->route('finance-settings.index', ['tab' => 'tags']);
    }

    public function store(Request $request): JsonResponse
    {
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
        $validated['color'] = $validated['color'] ?? '#696CFF';
        $tag = FinanceTag::create($validated);
        AuditLog::record('tag_created', null, null, [
            'created_by' => auth()->user()->username,
            'name' => $tag->name,
            'color' => $tag->color,
        ]);
        return response()->json([], 201);
    }

    public function edit(FinanceTag $financeTag): JsonResponse
    {
        return response()->json($financeTag->only(['id', 'name', 'color']));
    }

    public function update(Request $request, FinanceTag $financeTag): JsonResponse
    {
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
        $oldValues = [
            'name' => $financeTag->name,
            'color' => $financeTag->color,
        ];
        $financeTag->fill($validated);
        if (!$financeTag->isDirty()) {
            return response()->json([], 204);
        }
        $financeTag->save();
        $newValues = [
            'name' => $financeTag->name,
            'color' => $financeTag->color,
        ];
        AuditLog::record('tag_updated', null, $oldValues, $newValues);
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
        AuditLog::record('tag_deleted', null, $deletedInfo, null);
        $financeTag->delete();
        return response()->json([], 200);
    }
}