<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    protected $fillable = [
        'project_id', 'project_phase_id', 'parent_id', 'name', 'description', 'assignee_id',
        'estimate_hours', 'actual_hours', 'complexity', 'impact', 'weight',
        'due_date', 'status', 'progress_mode', 'manual_progress_pct',
        'volume_total', 'volume_done', 'checklist_total', 'checklist_done', 'category',
        'blocked_reason', 'blocked_at', 'sort_order',
    ];

    protected $casts = [
        'due_date' => 'date',
        'blocked_at' => 'datetime',
        'estimate_hours' => 'decimal:2',
        'actual_hours' => 'decimal:2',
        'weight' => 'decimal:6',
        'manual_progress_pct' => 'decimal:2',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function projectPhase(): BelongsTo
    {
        return $this->belongsTo(ProjectPhase::class, 'project_phase_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Task::class, 'parent_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TaskAttachment::class);
    }

    public function dependencyRowsAsSuccessor(): HasMany
    {
        return $this->hasMany(TaskDependency::class, 'successor_task_id');
    }

    public function dependencyRowsAsPredecessor(): HasMany
    {
        return $this->hasMany(TaskDependency::class, 'predecessor_task_id');
    }

    public function predecessors(): BelongsToMany
    {
        return $this->belongsToMany(
            Task::class,
            'task_dependencies',
            'successor_task_id',
            'predecessor_task_id'
        )->withTimestamps();
    }

    public function successors(): BelongsToMany
    {
        return $this->belongsToMany(
            Task::class,
            'task_dependencies',
            'predecessor_task_id',
            'successor_task_id'
        )->withTimestamps();
    }
}
