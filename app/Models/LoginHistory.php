<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginHistory extends Model
{
    public const EVENT_LOGIN_SUCCESS = 'login.success';

    public const EVENT_LOGIN_FAILED = 'login.failed';

    public const EVENT_LOGOUT = 'logout';

    public const EVENT_LOCKOUT = 'account.lockout';

    public const EVENT_PASSWORD_CHANGED = 'password.changed';

    public const EVENT_PASSWORD_RESET_REQUESTED = 'password.reset_requested';

    public const EVENT_PASSWORD_RESET_COMPLETED = 'password.reset_completed';

    public const EVENT_SESSION_TERMINATED = 'session.terminated';

    public const EVENT_SUSPICIOUS_ACTIVITY = 'security.suspicious';

    public const EVENT_EMAIL_VERIFIED = 'email.verified';

    protected $fillable = [
        'user_id',
        'ip_address',
        'user_agent',
        'device_fingerprint',
        'event',
        'location_country',
        'meta',
        'is_suspicious',
    ];

    protected $casts = [
        'meta' => 'array',
        'is_suspicious' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
