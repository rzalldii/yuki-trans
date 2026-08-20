<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinanceWallet extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'initial_balance',
        'current_balance',
    ];

    protected $casts = [
        'initial_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(FinanceTransaction::class, 'wallet_id');
    }

    public function recalculateBalance(): void
    {
        $income = $this->transactions()
            ->whereIn('type', ['income', 'transfer_in'])
            ->sum('amount');

        $expense = $this->transactions()
            ->whereIn('type', ['expense', 'transfer_out'])
            ->sum('amount');

        $this->update([
            'current_balance' => $this->initial_balance + $income - $expense,
        ]);
    }

    public function scopeOfName($query, string $name)
    {
        return $query->where('name', $name);
    }
}