<?php

namespace App\Models;

use App\Enums\ContractPaymentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractPayment extends Model
{
    protected $fillable = [
        'contract_id',
        'due_date',
        'amount',
        'status',
        'paid_at',
    ];

    protected $casts = [
        'due_date' => 'date',
        'amount' => 'decimal:2',
        'status' => ContractPaymentStatus::class,
        'paid_at' => 'datetime',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    /** @param  Builder<ContractPayment>  $query */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', ContractPaymentStatus::Pending->value);
    }

    /**
     * Payments whose parent contract is currently active (for reporting / reminders).
     *
     * @param  Builder<ContractPayment>  $query
     */
    public function scopeForActiveContracts(Builder $query): Builder
    {
        return $query->whereHas('contract', fn (Builder $q) => $q->active());
    }
}
