<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

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

    public function taskParticipants(): HasMany
    {
        return $this->hasMany(TaskParticipant::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @param  list<int>  $assigneeIds
     * @param  list<int>  $ownerIds
     * @param  list<int>  $followerIds
     */
    public function syncTaskParticipants(array $assigneeIds, array $ownerIds, array $followerIds): void
    {
        $assignees = $this->dedupePositiveIds($assigneeIds);
        $owners = $this->dedupePositiveIds($ownerIds);
        $followers = $this->dedupePositiveIds($followerIds);

        $this->taskParticipants()->delete();

        $sort = 0;
        foreach ($assignees as $uid) {
            $this->taskParticipants()->create([
                'user_id' => $uid,
                'role' => TaskParticipant::ROLE_ASSIGNEE,
                'sort_order' => $sort++,
            ]);
        }

        $sort = 0;
        foreach ($owners as $uid) {
            $this->taskParticipants()->create([
                'user_id' => $uid,
                'role' => TaskParticipant::ROLE_OWNER,
                'sort_order' => $sort++,
            ]);
        }

        $sort = 0;
        foreach ($followers as $uid) {
            $this->taskParticipants()->create([
                'user_id' => $uid,
                'role' => TaskParticipant::ROLE_FOLLOWER,
                'sort_order' => $sort++,
            ]);
        }

        $primary = $assignees[0] ?? null;
        if ($primary === null && $this->assignee_id) {
            $primary = (int) $this->assignee_id;
        }

        $this->forceFill(['assignee_id' => $primary])->saveQuietly();
    }

    /**
     * Gắn assignee_ids / owner_ids / follower_ids (mảng id) để serialize API — không phải cột DB.
     */
    public function mergeParticipantArraysIntoAttributes(): void
    {
        $this->loadMissing('taskParticipants');
        /** @var Collection<int, TaskParticipant> $rows */
        $rows = $this->taskParticipants;
        $g = $rows->groupBy('role');
        $this->setAttribute(
            'assignee_ids',
            ($g->get(TaskParticipant::ROLE_ASSIGNEE, collect()))->sortBy('sort_order')->pluck('user_id')->values()->all(),
        );
        $this->setAttribute(
            'owner_ids',
            ($g->get(TaskParticipant::ROLE_OWNER, collect()))->sortBy('sort_order')->pluck('user_id')->values()->all(),
        );
        $this->setAttribute(
            'follower_ids',
            ($g->get(TaskParticipant::ROLE_FOLLOWER, collect()))->sortBy('sort_order')->pluck('user_id')->values()->all(),
        );
    }

    /**
     * @param  list<int|string>  $ids
     * @return list<int>
     */
    protected function dedupePositiveIds(array $ids): array
    {
        $out = [];
        $seen = [];

        foreach ($ids as $id) {
            $n = (int) $id;
            if ($n <= 0 || isset($seen[$n])) {
                continue;
            }
            $seen[$n] = true;
            $out[] = $n;
        }

        return $out;
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
