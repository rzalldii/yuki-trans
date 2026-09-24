<?php

declare(strict_types=1);

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\StoreTagRequest;
use App\Http\Requests\Finance\UpdateTagRequest;
use App\Http\Resources\Finance\TagResource;
use App\Models\Finance\Tag;
use App\Services\Finance\TagService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class TagController extends Controller
{
    public function __construct(
        protected TagService $tagService
    ) {}

    public function index(): RedirectResponse
    {
        return redirect()->route('finance-master-data.index', ['tab' => 'tags']);
    }

    public function store(StoreTagRequest $request): JsonResponse
    {
        Gate::authorize('create', Tag::class);
        $tag = $this->tagService->createTag($request->validated());
        return response()->json([
            'success' => true,
            'data' => new TagResource($tag),
        ], 201);
    }

    public function edit(Tag $financeTag): JsonResponse
    {
        Gate::authorize('view', $financeTag);
        return response()->json((new TagResource($financeTag))->resolve());
    }

    public function update(UpdateTagRequest $request, Tag $financeTag): JsonResponse
    {
        Gate::authorize('update', $financeTag);
        $updated = $this->tagService->updateTag($financeTag, $request->validated());
        if ($updated === null) {
            return response()->json([], 204);
        }
        return response()->json([
            'success' => true,
            'data' => new TagResource($updated),
        ], 200);
    }

    public function destroy(Tag $financeTag): JsonResponse
    {
        Gate::authorize('delete', $financeTag);
        if (!$this->tagService->deleteTag($financeTag)) {
            return response()->json(['success' => false], 422);
        }
        return response()->json(['success' => true], 200);
    }
}