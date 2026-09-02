<?php

namespace App\Models\Finance;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FinanceRecurring extends Model
{
    protected $fillable = [
        'wallet_id',
        'category_id',
        'type',
        'amount',
        'description',
        'frequency',
        'start_date',
        'end_date',
        'next_due_date',
        'last_generated_at',
        'is_active',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'next_due_date' => 'date',
        'last_generated_at' => 'date',
        'is_active' => 'boolean',
    ];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(FinanceWallet::class, 'wallet_id')->withTrashed();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(FinanceCategory::class, 'category_id')->withTrashed();
    }

    public function generatedTransactions(): HasMany
    {
        return $this->hasMany(FinanceTransaction::class, 'recurring_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(
            FinanceTag::class,
            'finance_recurring_tag',
            'recurring_id',
            'tag_id'
        )->withTrashed();
    }

    public function calculateNextDueDate(?Carbon $from = null): ?Carbon
    {
        $fromDate = $from ?? ($this->next_due_date ? Carbon::parse($this->next_due_date) : ($this->last_generated_at ? Carbon::parse($this->last_generated_at) : Carbon::parse($this->start_date)));
        $next = match ($this->frequency) {
            'daily' => $fromDate->copy()->addDay(),
            'weekly' => $fromDate->copy()->addWeek(),
            'monthly' => $fromDate->copy()->addMonth(),
            'yearly' => $fromDate->copy()->addYear(),
        };
        if ($this->end_date && $next->greaterThan($this->end_date)) {
            return null;
        }
        return $next;
    }

    public function executeTransaction(?int $userId = null): ?FinanceTransaction
    {
        $userId = $userId ?? auth()->id() ?? User::where('role', 'admin')->value('id') ?? 1;
        $wallet = $this->wallet;
        $txDate = $this->next_due_date ?? now()->toDateString();
        $description = !empty($this->description) ? $this->description . ' (Auto)' : ($this->category->name ?? 'Recurring') . ' (Auto)';
        $transactionData = [
            'user_id' => $userId,
            'wallet_id' => $this->wallet_id,
            'category_id' => $this->category_id,
            'type' => $this->type,
            'amount' => $this->amount,
            'description' => $description,
            'transaction_date' => $txDate,
            'recurring_id' => $this->id,
        ];
        $transaction = DB::transaction(function () use ($transactionData, $wallet) {
            $transaction = FinanceTransaction::create($transactionData);
            if ($this->tags()->exists()) {
                $transaction->tags()->sync($this->tags->pluck('id'));
            }
            if ($wallet) {
                if ($this->type === 'income') {
                    $wallet->increment('current_balance', $this->amount);
                } else {
                    $wallet->decrement('current_balance', $this->amount);
                }
            }
            return $transaction;
        });
        $nextDue = $this->calculateNextDueDate($txDate instanceof Carbon ? $txDate : Carbon::parse($txDate));
        $this->update([
            'last_generated_at' => $txDate,
            'next_due_date' => $nextDue,
            'is_active' => $nextDue !== null,
        ]);
        return $transaction;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDueOn($query, $date)
    {
        return $query->whereNotNull('next_due_date')->where('next_due_date', '<=', $date);
    }
}