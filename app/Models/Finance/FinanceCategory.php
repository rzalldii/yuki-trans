<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinanceCategory extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'type',
        'budget',
    ];

    protected $casts = [
        'budget' => 'decimal:2',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(FinanceTransaction::class, 'category_id');
    }

    public function recurringTransactions(): HasMany
    {
        return $this->hasMany(FinanceRecurringTransaction::class, 'category_id');
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