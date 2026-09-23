<?php

declare(strict_types=1);

namespace App\Models\Finance;

use App\Enums\Finance\TransactionType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

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

    protected static function booted(): void
    {
        static::saved(function () {
            DB::afterCommit(function () {
                if (Cache::supportsTags()) {
                    Cache::tags(['finance'])->flush();
                }
                Cache::forget('finance_net_balance');
            });
        });
        static::deleted(function () {
            DB::afterCommit(function () {
                if (Cache::supportsTags()) {
                    Cache::tags(['finance'])->flush();
                }
                Cache::forget('finance_net_balance');
            });
        });
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

    public function adjustBalance(TransactionType|string $type, float|string $amount): void
    {
        $typeName = $type instanceof TransactionType ? $type->value : $type;
        $amt = (float) $amount;
        if (in_array($typeName, [TransactionType::Income->value, TransactionType::TransferIn->value], true)) {
            $this->increment('current_balance', $amt);
        } else {
            $this->decrement('current_balance', $amt);
        }
    }

    public function revertBalance(TransactionType|string $type, float|string $amount): void
    {
        $typeName = $type instanceof TransactionType ? $type->value : $type;
        $amt = (float) $amount;
        if (in_array($typeName, [TransactionType::Income->value, TransactionType::TransferIn->value], true)) {
            $this->decrement('current_balance', $amt);
        } else {
            $this->increment('current_balance', $amt);
        }
    }

    public function recalculateBalance(): void
    {
        $income = (string) $this->transactions()
            ->whereIn('type', [TransactionType::Income->value, TransactionType::TransferIn->value])
            ->sum('amount');
        $expense = (string) $this->transactions()
            ->whereIn('type', [TransactionType::Expense->value, TransactionType::TransferOut->value])
            ->sum('amount');
        $initial = (string) ($this->initial_balance ?? '0.00');
        $withIncome = bcadd($initial, $income, 2);
        $finalBalance = bcsub($withIncome, $expense, 2);
        $this->update([
            'current_balance' => $finalBalance,
        ]);
    }

    public function scopeOfName(Builder $query, string $name): Builder
    {
        return $query->where('name', $name);
    }
}