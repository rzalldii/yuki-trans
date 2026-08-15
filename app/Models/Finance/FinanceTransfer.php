<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceTransfer extends Model
{
    protected $fillable = [
        'from_wallet_id',
        'to_wallet_id',
        'amount',
        'description',
        'transfer_date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transfer_date' => 'date',
    ];

    public function fromWallet(): BelongsTo
    {
        return $this->belongsTo(FinanceWallet::class, 'from_wallet_id')->withTrashed();
    }

    public function toWallet(): BelongsTo
    {
        return $this->belongsTo(FinanceWallet::class, 'to_wallet_id')->withTrashed();
    }
}