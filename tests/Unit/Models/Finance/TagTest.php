<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Finance;

use App\Models\Finance\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_random_color_returns_valid_preset_color(): void
    {
        $color = Tag::getRandomColor();
        $this->assertArrayHasKey($color, Tag::PRESET_COLORS);
    }

    public function test_find_or_create_by_name_creates_tag_with_trimmed_name_and_random_color(): void
    {
        $tag = Tag::findOrCreateByName('  Design  ');
        $this->assertEquals('Design', $tag->name);
        $this->assertArrayHasKey($tag->color, Tag::PRESET_COLORS);
        $tagAgain = Tag::findOrCreateByName('Design');
        $this->assertEquals($tag->id, $tagAgain->id);
        $this->assertEquals(1, Tag::where('name', 'Design')->count());
    }

    public function test_badge_class_accessor_maps_color_correctly(): void
    {
        $tag = new Tag(['color' => '#696cff']);
        $this->assertEquals('tag-badge-blue', $tag->badge_class);
        $tagCustom = new Tag(['color' => '#unknown']);
        $this->assertEquals('tag-badge-blue', $tagCustom->badge_class);
    }

    public function test_find_or_create_by_name_restores_trashed_tag(): void
    {
        $tag = Tag::create([
            'name' => 'Marketing',
            'color' => '#ff3e1d',
        ]);
        $tag->delete();
        $this->assertSoftDeleted('finance_tags', ['id' => $tag->id]);
        $restoredTag = Tag::findOrCreateByName('Marketing');
        $this->assertEquals($tag->id, $restoredTag->id);
        $this->assertFalse($restoredTag->fresh()->trashed());
        $this->assertEquals(1, Tag::withTrashed()->where('name', 'Marketing')->count());
    }
}