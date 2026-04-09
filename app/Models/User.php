<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Pivots\TeamUserPivot;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /** @var string */
    protected $connection = 'cms';

    public function getConnectionName(): ?string
    {
        if ($this->connection === 'cms'
            && app()->environment('testing')
            && config('database.default') === 'sqlite') {
            return config('database.default');
        }

        return $this->connection;
    }

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'google_id',
        'avatar_path',
        'profile_updated_at',
    ];

    /**
     * @var array<int, string>
     */
    protected $appends = [
        'avatar_url',
    ];

    /**
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'password_history',
        'avatar_path',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'password_changed_at' => 'datetime',
        'locked_until' => 'datetime',
        'last_login_at' => 'datetime',
        'profile_updated_at' => 'datetime',
        'terms_accepted_at' => 'datetime',
        'must_change_password' => 'boolean',
        'security_banner_acknowledged' => 'boolean',
        'password_history' => 'array',
        'password_expiry_days' => 'integer',
        'lockout_threshold' => 'integer',
        'failed_login_count' => 'integer',
        'session_timeout_minutes' => 'integer',
    ];

    public function userInfo(): HasOne
    {
        return $this->hasOne(UserInfo::class);
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_user')
            ->using(TeamUserPivot::class)
            ->withPivot(['role', 'position', 'permissions'])
            ->withTimestamps();
    }

    public function ownedProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'owner_id');
    }

    public function assignedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assignee_id');
    }

    public function kaizens(): HasMany
    {
        return $this->hasMany(Kaizen::class, 'submitter_id');
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class, 'person_id');
    }

    public function kpiSnapshots(): MorphMany
    {
        return $this->morphMany(KpiSnapshot::class, 'entity');
    }

    public function loginHistories(): HasMany
    {
        return $this->hasMany(LoginHistory::class);
    }

    public function activeSessions(): HasMany
    {
        return $this->hasMany(ActiveSession::class);
    }

    public function otpCodes(): HasMany
    {
        return $this->hasMany(OtpCode::class);
    }

    public function permissionOverrides(): HasMany
    {
        return $this->hasMany(UserPermissionOverride::class);
    }

    public function isLocked(): bool
    {
        if ($this->locked_until === null) {
            return false;
        }

        return $this->locked_until->isFuture();
    }

    public function isPasswordExpired(): bool
    {
        $days = (int) ($this->password_expiry_days ?? 90);
        if ($days <= 0) {
            return false;
        }

        $anchor = $this->password_changed_at ?? $this->created_at ?? now();

        return $anchor->copy()->addDays($days)->isPast();
    }

    /**
     * Second step (email OTP) after password when PPMS_LOGIN_MFA=true and role is listed.
     */
    public function requiresMfa(): bool
    {
        if (! config('ppms.login_mfa_enabled', false)) {
            return false;
        }

        $roles = config('ppms.login_mfa_roles', []);

        return in_array($this->role, $roles, true);
    }

    public function hasAcknowledgedBanner(): bool
    {
        return (bool) $this->security_banner_acknowledged;
    }

    public function getAvatarUrlAttribute(): ?string
    {
        if (! $this->avatar_path) {
            return null;
        }

        return Storage::disk('public')->url($this->avatar_path);
    }
}
