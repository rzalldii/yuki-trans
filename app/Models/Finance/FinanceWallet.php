<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;

class FinanceWallet extends Model
{
    protected $fillable = [
        'name',
        'initial_balance',
    ];

    protected $casts = [
        'initial_balance' => 'decimal:2',
    ];
}