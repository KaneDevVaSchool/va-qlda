<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Task;

class ProjectProgressService
{
    public function syncProjectProgress(Project $project): void
    {
        $query = Task::query()->where('project_id', $project->id);

        $totalWeight = (clone $query)->sum('weight');
        $completedWeight = (clone $query)->where('status', 'done')->sum('weight');

        $progress = $totalWeight > 0
            ? round(($completedWeight / $totalWeight) * 100, 4)
            : 0.0;

        $project->progress = $progress;
        $this->applyStatusFromProgress($project);
        $this->syncSuggestedPhase($project);
        $project->saveQuietly();
    }

    /**
     * BR-PM-02: phase theo lifecycle task (planning → dev → uat → done).
     */
    protected function syncSuggestedPhase(Project $project): void
    {
        $tasks = $project->tasks()->get();
        if ($tasks->isEmpty()) {
            return;
        }

        $allDone = $tasks->where('status', '!=', 'done')->isEmpty();
        $hasStartedWork = $tasks->whereNotIn('status', ['todo'])->isNotEmpty();

        if ($allDone && (float) $project->progress >= 99.99) {
            if (in_array($project->phase, ['planning', 'development'], true)) {
                $project->phase = 'uat';
            } elseif ($project->phase === 'uat') {
                $project->phase = 'done';
            }

            return;
        }

        if ($hasStartedWork && $project->phase === 'planning') {
            $project->phase = 'development';
        }
    }

    protected function applyStatusFromProgress(Project $project): void
    {
        if ($project->status === 'blocked') {
            return;
        }

        if ($project->deadline && $project->deadline->isPast() && (float) $project->progress < 100) {
            $project->status = 'delayed';

            return;
        }

        $this->applyTimelineAtRisk($project);

        if ((float) $project->progress >= 99.999 && $project->status !== 'delayed') {
            $project->status = 'on_track';
        }
    }

    /**
     * So sánh tiến độ thực tế với đường thời gian (trễ ≥20% kỳ vọng → At Risk).
     */
    protected function applyTimelineAtRisk(Project $project): void
    {
        if (! $project->deadline || ! $project->created_at || $project->deadline->isPast()) {
            return;
        }

        if ($project->status === 'delayed') {
            return;
        }

        $start = $project->created_at->copy()->startOfDay();
        $end = $project->deadline->copy()->startOfDay();
        $totalDays = max(1, $start->diffInDays($end));
        $elapsed = min($totalDays, $start->diffInDays(now()->startOfDay()));
        $expectedProgress = min(100.0, ($elapsed / $totalDays) * 100);

        if ((float) $project->progress + 20 < $expectedProgress) {
            $project->status = 'at_risk';
        }
    }
}
