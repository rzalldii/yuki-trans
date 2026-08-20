<?php

namespace App\Models\Finance;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class FinanceTransaction extends Model
{
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

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(FinanceCategory::class, 'category_id')->withTrashed();
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(FinanceWallet::class, 'wallet_id')->withTrashed();
    }

    public function transferPair(): BelongsTo
    {
        return $this->belongsTo(FinanceTransaction::class, 'transfer_pair_id');
    }

    public function recurringSource(): BelongsTo
    {
        return $this->belongsTo(FinanceRecurringTransaction::class, 'recurring_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(
            FinanceTag::class,
            'finance_transaction_tag',
            'transaction_id',
            'tag_id'
        );
    }

    public function isTransfer(): bool
    {
        return in_array($this->type, ['transfer_in', 'transfer_out']);
    }

    public function isRecurring(): bool
    {
        return $this->recurring_id !== null;
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}