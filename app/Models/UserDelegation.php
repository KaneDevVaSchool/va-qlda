<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserDelegation extends Model
{
    protected $fillable = [
        'delegator_id',
        'delegatee_id',
        'scope',
        'expires_at',
    ];

    /**
     * @var array<int, string>
     */
    protected $appends = [
        'is_expired',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'expires_at' => 'datetime',
    ];

    protected function isExpired(): Attribute
    {
        return Attribute::get(
            fn (): bool => $this->expires_at !== null && $this->expires_at->isPast(),
        );
    }

    public function delegator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delegator_id');
    }

    public function delegatee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delegatee_id');
    }
}
