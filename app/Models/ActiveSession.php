<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActiveSession extends Model
{
    protected $fillable = [
        'user_id',
        'session_id_hash',
        'ip_address',
        'user_agent',
        'device_label',
        'is_current',
        'last_activity_at',
        'expires_at',
        'personal_access_token_id',
    ];

    protected $casts = [
        'is_current' => 'boolean',
        'last_activity_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        if ($this->expires_at === null) {
            return false;
        }

        return $this->expires_at->isPast();
    }
}
