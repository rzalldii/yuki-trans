<?php

declare(strict_types=1);

namespace App\Models\Finance;

use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Builder;
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

    public function adjustBalance(TransactionType|string $type, float $amount): void
    {
        $typeName = $type instanceof TransactionType ? $type->value : $type;
        if (in_array($typeName, [TransactionType::Income->value, TransactionType::TransferIn->value], true)) {
            $this->increment('current_balance', $amount);
        } else {
            $this->decrement('current_balance', $amount);
        }
    }

    public function revertBalance(TransactionType|string $type, float $amount): void
    {
        $typeName = $type instanceof TransactionType ? $type->value : $type;
        if (in_array($typeName, [TransactionType::Income->value, TransactionType::TransferIn->value], true)) {
            $this->decrement('current_balance', $amount);
        } else {
            $this->increment('current_balance', $amount);
        }
    }

    public function recalculateBalance(): void
    {
        $income = $this->transactions()
            ->whereIn('type', [TransactionType::Income->value, TransactionType::TransferIn->value])
            ->sum('amount');
        $expense = $this->transactions()
            ->whereIn('type', [TransactionType::Expense->value, TransactionType::TransferOut->value])
            ->sum('amount');
        $this->update([
            'current_balance' => $this->initial_balance + $income - $expense,
        ]);
    }

    public function scopeOfName(Builder $query, string $name): Builder
    {
        return $query->where('name', $name);
    }
}