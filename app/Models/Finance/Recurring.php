<?php

declare(strict_types=1);

namespace App\Models\Finance;

use App\Enums\Frequency;
use App\Enums\RecurringType;
use App\Services\Finance\RecurringExecutionService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use InvalidArgumentException;

class Recurring extends Model
{
    protected $table = 'finance_recurrings';

    protected $fillable = [
        'wallet_id',
        'to_wallet_id',
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

    protected function casts(): array
    {
        return [
            'type' => RecurringType::class,
            'frequency' => Frequency::class,
            'amount' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
            'next_due_date' => 'date',
            'last_generated_at' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'wallet_id')->withTrashed();
    }

    public function toWallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'to_wallet_id')->withTrashed();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id')->withTrashed();
    }

    public function generatedTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'recurring_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(
            Tag::class,
            'finance_recurring_tag',
            'recurring_id',
            'tag_id'
        )->withTrashed();
    }

    public function calculateNextDueDate(?Carbon $from = null): ?Carbon
    {
        $base = $from ?? ($this->last_generated_at ? Carbon::parse($this->last_generated_at) : ($this->next_due_date ? Carbon::parse($this->next_due_date) : Carbon::parse($this->start_date)));
        $baseDate = $base instanceof Carbon ? $base->copy() : Carbon::parse($base);
        $frequency = $this->frequency instanceof Frequency
            ? $this->frequency
            : Frequency::tryFrom((string) $this->frequency);
        if (!$frequency) {
            throw new InvalidArgumentException("Unknown frequency: {$this->frequency}");
        }
        $next = $frequency->addToDate($baseDate);
        if ($this->end_date && $next->greaterThan(Carbon::parse($this->end_date)->endOfDay())) {
            return null;
        }
        return $next;
    }

    public function executeTransaction(?int $userId = null): ?Transaction
    {
        return app(RecurringExecutionService::class)->executeRecurring($this, $userId);
    }

    public function isTransfer(): bool
    {
        return $this->type === RecurringType::Transfer || $this->type === 'transfer';
    }

    public function isIncome(): bool
    {
        return $this->type === RecurringType::Income || $this->type === 'income';
    }

    public function isExpense(): bool
    {
        return $this->type === RecurringType::Expense || $this->type === 'expense';
    }

    public function typeValue(): string
    {
        return $this->type instanceof RecurringType ? $this->type->value : (string) $this->type;
    }

    public function typeLabel(): string
    {
        return $this->type instanceof RecurringType ? $this->type->label() : ucfirst((string) $this->type);
    }

    public function frequencyValue(): string
    {
        return $this->frequency instanceof Frequency ? $this->frequency->value : (string) $this->frequency;
    }

    public function frequencyLabel(): string
    {
        return $this->frequency instanceof Frequency ? $this->frequency->label() : ucfirst((string) $this->frequency);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDueOn($query, $date)
    {
        return $query->whereNotNull('next_due_date')->whereDate('next_due_date', '<=', $date);
    }
}