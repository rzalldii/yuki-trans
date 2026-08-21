<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinanceCategory extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'type',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    protected $appends = [
        'amount_label',
    ];

    protected function amountLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->type === 'income' ? 'Target' : 'Budget'
        );
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(FinanceTransaction::class, 'category_id');
    }

    public function recurringTransactions(): HasMany
    {
        return $this->hasMany(FinanceRecurringTransaction::class, 'category_id');
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

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }
}