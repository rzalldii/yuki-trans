<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Models\Audit\AuditLog;
use App\Models\Finance\Tag;
use Illuminate\Support\Facades\DB;

class TagService
{
    public function createTag(array $validated): Tag
    {
        $validated['color'] = !empty($validated['color']) ? strtolower($validated['color']) : Tag::getRandomColor();
        return DB::transaction(function () use ($validated) {
            $tag = Tag::create($validated);
            AuditLog::record('tag_created', null, null, [
                'name' => $tag->name,
                'color' => $tag->color,
            ]);
            return $tag;
        });
    }

    public function updateTag(Tag $tag, array $validated): ?Tag
    {
        $validated['color'] = !empty($validated['color']) ? strtolower($validated['color']) : ($tag->color ? strtolower($tag->color) : '#696cff');
        $oldValues = [
            'name' => $tag->name,
            'color' => $tag->color,
        ];
        $tag->fill($validated);
        if (!$tag->isDirty()) {
            return null;
        }
        return DB::transaction(function () use ($tag, $oldValues) {
            $tag->save();
            $newValues = [
                'name' => $tag->name,
                'color' => $tag->color,
            ];
            AuditLog::record('tag_updated', null, $oldValues, $newValues);
            return $tag;
        });
    }

    public function deleteTag(Tag $tag): bool
    {
        if ($tag->transactions()->exists() || $tag->recurrings()->exists()) {
            return false;
        }
        $deletedInfo = [
            'name' => $tag->name,
            'color' => $tag->color,
        ];
        DB::transaction(function () use ($tag, $deletedInfo) {
            AuditLog::record('tag_deleted', null, $deletedInfo, null);
            $tag->delete();
        });
        return true;
    }
}