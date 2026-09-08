<?php

namespace App\Models\Finance;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

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
        $next = match ($this->frequency) {
            'daily' => $baseDate->copy()->addDay(),
            'weekly' => $baseDate->copy()->addWeek(),
            'monthly' => $baseDate->copy()->addMonthNoOverflow(),
            'yearly' => $baseDate->copy()->addYearNoOverflow(),
            default => throw new \InvalidArgumentException("Unknown frequency: {$this->frequency}"),
        };
        if ($this->end_date && $next->greaterThan(Carbon::parse($this->end_date)->endOfDay())) {
            return null;
        }
        return $next;
    }

    public function executeTransaction(?int $userId = null): ?Transaction
    {
        $userId = $userId ?? auth()->id() ?? User::where('role', 'admin')->value('id') ?? 1;
        $txDate = $this->next_due_date ?? now()->toDateString();
        if ($this->type === 'transfer') {
            $fromWallet = $this->wallet;
            $toWallet = $this->toWallet;
            if (!$fromWallet || !$toWallet) {
                return null;
            }
            $description = !empty($this->description) ? $this->description . ' (Auto)' : 'Transfer (Auto)';
            $transaction = DB::transaction(function () use ($userId, $fromWallet, $toWallet, $txDate, $description) {
                $out = Transaction::create([
                    'user_id' => $userId,
                    'wallet_id' => $fromWallet->id,
                    'type' => 'transfer_out',
                    'amount' => $this->amount,
                    'description' => $description,
                    'transaction_date' => $txDate,
                    'recurring_id' => $this->id,
                ]);
                $in = Transaction::create([
                    'user_id' => $userId,
                    'wallet_id' => $toWallet->id,
                    'type' => 'transfer_in',
                    'amount' => $this->amount,
                    'description' => $description,
                    'transaction_date' => $txDate,
                    'recurring_id' => $this->id,
                ]);
                $out->update(['transfer_pair_id' => $in->id]);
                $in->update(['transfer_pair_id' => $out->id]);
                if ($this->relationLoaded('tags') ? $this->tags->isNotEmpty() : $this->tags()->exists()) {
                    $tagIds = $this->tags->pluck('id');
                    $out->tags()->sync($tagIds);
                    $in->tags()->sync($tagIds);
                }
                $fromWallet->adjustBalance('transfer_out', (float) $this->amount);
                $toWallet->adjustBalance('transfer_in', (float) $this->amount);
                return $out;
            });
        } else {
            $wallet = $this->wallet;
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
                $transaction = Transaction::create($transactionData);
                if ($this->relationLoaded('tags') ? $this->tags->isNotEmpty() : $this->tags()->exists()) {
                    $transaction->tags()->sync($this->tags->pluck('id'));
                }
                if ($wallet) {
                    $wallet->adjustBalance($this->type, (float) $this->amount);
                }
                return $transaction;
            });
        }
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