<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreTagRequest;
use App\Http\Requests\Finance\UpdateTagRequest;
use App\Models\Audit\AuditLog;
use App\Models\Finance\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class TagController extends Controller
{
    public function index()
    {
        return redirect()->route('finance-master-data.index', ['tab' => 'tags']);
    }

    public function store(StoreTagRequest $request): JsonResponse
    {
        Gate::authorize('create', Tag::class);
        $validated = $request->validated();
        $validated['color'] = !empty($validated['color']) ? strtolower($validated['color']) : Tag::getRandomColor();
        DB::transaction(function () use ($validated) {
            $tag = Tag::create($validated);
            AuditLog::record('tag_created', null, null, [
                'name' => $tag->name,
                'color' => $tag->color,
            ]);
        });
        return response()->json(['success' => true], 201);
    }

    public function edit(Tag $financeTag): JsonResponse
    {
        Gate::authorize('view', $financeTag);
        return response()->json([
            'id' => $financeTag->id,
            'name' => $financeTag->name,
            'color' => $financeTag->color,
        ]);
    }

    public function update(UpdateTagRequest $request, Tag $financeTag): JsonResponse
    {
        Gate::authorize('update', $financeTag);
        $validated = $request->validated();
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
        return response()->json(['success' => true], 200);
    }

    public function destroy(Tag $financeTag): JsonResponse
    {
        Gate::authorize('delete', $financeTag);
        if ($financeTag->transactions()->exists() || $financeTag->recurrings()->exists()) {
            return response()->json(['success' => false], 422);
        }
        $deletedInfo = [
            'name' => $financeTag->name,
            'color' => $financeTag->color,
        ];
        DB::transaction(function () use ($financeTag, $deletedInfo) {
            AuditLog::record('tag_deleted', null, $deletedInfo, null);
            $financeTag->delete();
        });
        return response()->json(['success' => true], 200);
    }
}