<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinanceCategory extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'name',
        'type',
    ];


    public function transactions(): HasMany
    {
        return $this->hasMany(FinanceTransaction::class, 'category_id');
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }
}