<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinanceTag extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'color',
    ];

    public function transactions(): BelongsToMany
    {
        return $this->belongsToMany(
            FinanceTransaction::class,
            'finance_transaction_tag',
            'tag_id',
            'transaction_id'
        );
    }
}