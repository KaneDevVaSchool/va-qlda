<?php

namespace App\Models;

use App\Enums\ContractApprovalStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractApproval extends Model
{
    protected $fillable = [
        'contract_id',
        'approver_id',
        'step',
        'status',
        'approved_at',
    ];

    protected $casts = [
        'status' => ContractApprovalStatus::class,
        'approved_at' => 'datetime',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
