<?php

declare(strict_types=1);

namespace App\Models\Finance;

use App\Enums\CategoryType;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use SoftDeletes;

    protected $table = 'finance_categories';

    protected $fillable = [
        'name',
        'type',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'type' => CategoryType::class,
            'amount' => 'decimal:2',
        ];
    }

    protected $appends = [
        'amount_label',
    ];

    protected function amountLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => ($this->type === CategoryType::Income || $this->type === 'income') ? 'Target' : 'Budget'
        );
    }

    public function recurrings(): HasMany
    {
        return $this->hasMany(Recurring::class, 'category_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'category_id');
    }

    public function getActualForMonth(string $periodMonth): float
    {
        $startDate = $periodMonth . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));
        return (float) $this->transactions()
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->sum('amount');
    }

    public function getSpentForMonth(string $periodMonth): float
    {
        $startDate = $periodMonth . '-01';
        $endDate = date('Y-m-t', strtotime($startDate));
        return (float) $this->transactions()
            ->where('type', 'expense')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->sum('amount');
    }

    public function scopeOfType($query, CategoryType|string $type)
    {
        $val = $type instanceof CategoryType ? $type->value : $type;
        return $query->where('type', $val);
    }
}