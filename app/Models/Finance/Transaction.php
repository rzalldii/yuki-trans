<?php

declare(strict_types=1);

namespace App\Models\Finance;

use App\Enums\TransactionType;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Cache;

class Transaction extends Model
{
    protected $table = 'finance_transactions';

    protected $fillable = [
        'user_id',
        'wallet_id',
        'category_id',
        'type',
        'amount',
        'description',
        'transaction_date',
        'transfer_pair_id',
        'recurring_id',
    ];

    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'amount' => 'decimal:2',
            'transaction_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function () {
            Cache::forget('finance_net_balance');
            Cache::put('finance_summary_version', (int) microtime(true));
        });
        static::deleted(function () {
            Cache::forget('finance_net_balance');
            Cache::put('finance_summary_version', (int) microtime(true));
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'wallet_id')->withTrashed();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id')->withTrashed();
    }

    public function transferPair(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transfer_pair_id');
    }

    public function recurringSource(): BelongsTo
    {
        return $this->belongsTo(Recurring::class, 'recurring_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(
            Tag::class,
            'finance_transaction_tag',
            'transaction_id',
            'tag_id'
        )->withTrashed();
    }

    public function isTransfer(): bool
    {
        return $this->type instanceof TransactionType
            ? $this->type->isTransfer()
            : in_array($this->type, [TransactionType::TransferIn->value, TransactionType::TransferOut->value], true);
    }

    public function isIncome(): bool
    {
        return $this->type === TransactionType::Income || $this->type === 'income';
    }

    public function isExpense(): bool
    {
        return $this->type === TransactionType::Expense || $this->type === 'expense';
    }

    public function typeValue(): string
    {
        return $this->type instanceof TransactionType ? $this->type->value : (string) $this->type;
    }

    public function typeLabel(): string
    {
        return $this->type instanceof TransactionType ? $this->type->label() : ucfirst((string) $this->type);
    }

    public function isRecurring(): bool
    {
        return $this->recurring_id !== null;
    }

    public function scopeOfType(Builder $query, TransactionType|string $type): Builder
    {
        $val = $type instanceof TransactionType ? $type->value : $type;
        return $query->where('type', $val);
    }

    public function scopeBetweenDates(Builder $query, $startDate, $endDate): Builder
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }
}