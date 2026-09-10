<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreTagRequest;
use App\Http\Requests\Finance\UpdateTagRequest;
use App\Models\Finance\Tag;
use App\Services\Finance\TagService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class TagController extends Controller
{
    public function index()
    {
        return redirect()->route('finance-master-data.index', ['tab' => 'tags']);
    }

    public function store(StoreTagRequest $request, TagService $service): JsonResponse
    {
        Gate::authorize('create', Tag::class);
        $service->createTag($request->validated());
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

    public function update(UpdateTagRequest $request, Tag $financeTag, TagService $service): JsonResponse
    {
        Gate::authorize('update', $financeTag);
        $updated = $service->updateTag($financeTag, $request->validated());
        if ($updated === null) {
            return response()->json([], 204);
        }
        return response()->json(['success' => true], 200);
    }

    public function destroy(Tag $financeTag, TagService $service): JsonResponse
    {
        Gate::authorize('delete', $financeTag);
        if (!$service->deleteTag($financeTag)) {
            return response()->json(['success' => false], 422);
        }
        return response()->json(['success' => true], 200);
    }
}