<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Project extends Model
{
    protected $fillable = [
        'name', 'code', 'type', 'phase', 'status', 'owner_id', 'team_id', 'deadline', 'start_date', 'actual_start_date',
        'description', 'estimated_value', 'progress', 'progress_calc', 'archived_at',
        'customer_name', 'customer_email', 'suppliers', 'process_timeline',
        'stakeholder_emails', 'csat_invites_sent', 'csat_survey_sent_at',
        'labels',
        'executor_user_ids', 'follower_user_ids', 'permission_preset',
    ];

    protected $casts = [
        'deadline' => 'date',
        'start_date' => 'date',
        'actual_start_date' => 'date',
        'archived_at' => 'datetime',
        'progress' => 'decimal:4',
        'estimated_value' => 'decimal:2',
        'stakeholder_emails' => 'array',
        'suppliers' => 'array',
        'process_timeline' => 'array',
        'csat_survey_sent_at' => 'datetime',
        'labels' => 'array',
        'executor_user_ids' => 'array',
        'follower_user_ids' => 'array',
    ];

    /**
     * @param  list<string>|null  $labels
     * @return list<string>|null null = do not change (caller); [] = clear
     */
    public static function normalizeLabelList(?array $labels): ?array
    {
        if ($labels === null) {
            return null;
        }
        $out = [];
        $seen = [];
        foreach ($labels as $raw) {
            $t = trim((string) $raw);
            if ($t === '') {
                continue;
            }
            if (function_exists('mb_strlen') && mb_strlen($t) > 64) {
                $t = mb_substr($t, 0, 64);
            } elseif (strlen($t) > 64) {
                $t = substr($t, 0, 64);
            }
            $k = function_exists('mb_strtolower') ? mb_strtolower($t) : strtolower($t);
            if (isset($seen[$k])) {
                continue;
            }
            $seen[$k] = true;
            $out[] = $t;
            if (count($out) >= 30) {
                break;
            }
        }

        return $out;
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'team_id');
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

    public function documents(): HasMany
    {
        return $this->hasMany(ProjectDocument::class)->orderBy('sort_order')->orderBy('id');
    }

    public function phases(): HasMany
    {
        return $this->hasMany(ProjectPhase::class)->orderBy('sort_order')->orderBy('id');
    }

    public function supplies(): HasMany
    {
        return $this->hasMany(ProjectSupply::class)->orderBy('sort_order')->orderBy('id');
    }
}
