<?php

namespace App\Models;

use App\Enums\ContractStatus;
use App\Enums\PaymentCycle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contract extends Model
{
    protected $fillable = [
        'code',
        'vendor_id',
        'product_id',
        'department_id',
        'scope',
        'status',
        'start_date',
        'end_date',
        'total_value',
        'payment_cycle',
        'created_by',
        'approved_by',
    ];

    protected $casts = [
        'status' => ContractStatus::class,
        'payment_cycle' => PaymentCycle::class,
        'start_date' => 'date',
        'end_date' => 'date',
        'total_value' => 'decimal:2',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(ContractVersion::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(ContractFile::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(ContractPayment::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(ContractApproval::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ContractLog::class);
    }

    /** @param  Builder<Contract>  $query */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', ContractStatus::Active->value);
    }
}
