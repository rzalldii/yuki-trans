<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    use SoftDeletes;

    protected $table = 'finance_wallets';

    protected $fillable = [
        'name',
        'initial_balance',
        'current_balance',
    ];

    protected function casts(): array
    {
        return [
            'initial_balance' => 'decimal:2',
            'current_balance' => 'decimal:2',
        ];
    }

    public function recurrings(): HasMany
    {
        return $this->hasMany(Recurring::class, 'wallet_id');
    }

    public function toRecurrings(): HasMany
    {
        return $this->hasMany(Recurring::class, 'to_wallet_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'wallet_id');
    }

    public function adjustBalance(string $type, float $amount): void
    {
        if (in_array($type, ['income', 'transfer_in'], true)) {
            $this->increment('current_balance', $amount);
        } else {
            $this->decrement('current_balance', $amount);
        }
    }

    public function revertBalance(string $type, float $amount): void
    {
        if (in_array($type, ['income', 'transfer_in'], true)) {
            $this->decrement('current_balance', $amount);
        } else {
            $this->increment('current_balance', $amount);
        }
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