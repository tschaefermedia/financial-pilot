<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'type', 'starting_balance', 'currency', 'icon', 'color', 'sort_order', 'is_active'])]
class Account extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'starting_balance' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function recurringTemplates(): HasMany
    {
        return $this->hasMany(RecurringTemplate::class);
    }

    public function getCurrentBalanceAttribute(): float
    {
        $txSum = $this->transactions_sum_amount ?? $this->transactions()->sum('amount');

        return round((float) $this->starting_balance + (float) $txSum, 2);
    }

    public function scopeActiveOrdered(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('name');
    }
}
