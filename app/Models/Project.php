<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Project extends Model
{
    protected $fillable = [
        'name', 'type', 'phase', 'status', 'owner_id', 'deadline',
        'description', 'progress', 'archived_at',
        'stakeholder_emails', 'csat_invites_sent', 'csat_survey_sent_at',
    ];

    protected $casts = [
        'deadline' => 'date',
        'archived_at' => 'datetime',
        'progress' => 'decimal:4',
        'stakeholder_emails' => 'array',
        'csat_survey_sent_at' => 'datetime',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function kpiSnapshots(): MorphMany
    {
        return $this->morphMany(KpiSnapshot::class, 'entity');
    }

    public function csatResponses(): HasMany
    {
        return $this->hasMany(CsatResponse::class);
    }
}
