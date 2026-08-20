<?php

namespace App\Models\Finance;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinanceRecurringTransaction extends Model
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

    public function calculateNextDueDate(): ?\Carbon\Carbon
    {
        $from = $this->last_generated_at ?? $this->start_date;

        $next = match ($this->frequency) {
            'daily' => $from->copy()->addDay(),
            'weekly' => $from->copy()->addWeek(),
            'monthly' => $from->copy()->addMonth(),
            'yearly' => $from->copy()->addYear(),
        };

        if ($this->end_date && $next->greaterThan($this->end_date)) {
            return null;
        }

        return $next;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDueOn($query, $date)
    {
        return $query->where('next_due_date', '<=', $date);
    }
}