<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tag extends Model
{
    use SoftDeletes;

    protected $table = 'finance_tags';

    protected $fillable = [
        'name',
        'color',
    ];

    public const PRESET_COLORS = [
        '#696cff' => 'tag-badge-blue',
        '#8592a3' => 'tag-badge-gray',
        '#71dd37' => 'tag-badge-green',
        '#ff3e1d' => 'tag-badge-red',
        '#ffab00' => 'tag-badge-yellow',
        '#03c3ec' => 'tag-badge-cyan',
        '#233446' => 'tag-badge-dark',
    ];

    public static function getRandomColor(): string
    {
        $colors = array_keys(self::PRESET_COLORS);
        return $colors[array_rand($colors)];
    }

    public static function findOrCreateByName(string $name): self
    {
        $trimmed = trim($name);
        $tag = self::withTrashed()->where('name', $trimmed)->first();
        if ($tag) {
            if ($tag->trashed()) {
                $tag->restore();
            }
            return $tag;
        }
        return self::create([
            'name' => $trimmed,
            'color' => self::getRandomColor(),
        ]);
    }

    protected function badgeClass(): Attribute
    {
        return Attribute::make(
            get: fn () => self::PRESET_COLORS[strtolower($this->color ?? '')] ?? 'tag-badge-blue'
        );
    }

    public function recurrings(): BelongsToMany
    {
        return $this->belongsToMany(
            Recurring::class,
            'finance_recurring_tag',
            'tag_id',
            'recurring_id'
        );
    }

    public function transactions(): BelongsToMany
    {
        return $this->belongsToMany(
            Transaction::class,
            'finance_transaction_tag',
            'tag_id',
            'transaction_id'
        );
    }
}