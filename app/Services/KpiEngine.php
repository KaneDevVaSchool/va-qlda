<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Task;
use App\Models\TaskParticipant;
use App\Models\User;

class KpiEngine
{
    /**
     * Task được tính cho user nếu là assignee chính (cột) hoặc participant role assignee.
     */
    protected function tasksForUserAsAssignee(int $userId)
    {
        return Task::query()->where(function ($q) use ($userId) {
            $q->where('assignee_id', $userId)
                ->orWhereHas('taskParticipants', fn ($p) => $p->where('user_id', $userId)->where('role', TaskParticipant::ROLE_ASSIGNEE));
        });
    }

    /**
     * BR-KPI-01 Performance = Σ(Completed Weight) / Σ(Planned Weight) × 100%
     */
    public function personPerformancePercent(int $userId): float
    {
        $q = $this->tasksForUserAsAssignee($userId);

        $planned = (clone $q)->sum('weight');
        $completed = (clone $q)->where('status', 'done')->sum('weight');

        if ($planned <= 0) {
            return 0.0;
        }

        return round(($completed / $planned) * 100, 4);
    }

    /**
     * BR-KPI-02 Efficiency = Σ(Estimate) / Σ(Actual) on completed tasks
     */
    public function personEfficiencyRatio(int $userId): ?float
    {
        $q = Task::query()
            ->where('assignee_id', $userId)
            ->where('status', 'done');

        $est = (float) (clone $q)->sum('estimate_hours');
        $act = (float) (clone $q)->sum('actual_hours');

        if ($act <= 0) {
            return null;
        }

        return round($est / $act, 4);
    }

    /**
     * BR-KPI-03 SLA Rate (Type 1) = resolved on time / total closed tasks
     */
    public function maintenanceSlaRateForUser(int $userId): ?float
    {
        $q = $this->tasksForUserAsAssignee($userId)
            ->whereHas('project', fn ($p) => $p->where('type', 'maintenance'))
            ->whereIn('status', ['done']);

        $total = (clone $q)->count();
        if ($total === 0) {
            return null;
        }

        $onTime = (clone $q)->where(function ($w) {
            $w->whereNull('due_date')
                ->orWhereColumn('updated_at', '<=', 'due_date');
        })->count();

        return round(($onTime / $total) * 100, 4);
    }

    /**
     * @param  list<int>|null  $userIds  null = toàn bộ người dùng hệ thống
     */
    public function teamAveragePerformance(?array $userIds = null): float
    {
        $dist = $this->teamPerformanceDistribution($userIds);

        return count($dist) > 0 ? round(array_sum($dist) / count($dist), 4) : 0.0;
    }

    /**
     * @param  list<int>|null  $userIds  null = toàn bộ người dùng hệ thống
     * @return array<int, float> user_id => performance %
     */
    public function teamPerformanceDistribution(?array $userIds = null): array
    {
        $ids = $userIds ?? User::query()->pluck('id')->all();
        $out = [];
        foreach ($ids as $id) {
            $id = (int) $id;
            $out[$id] = $this->personPerformancePercent($id);
        }

        return $out;
    }

    /**
     * BR-KPI-07: percentile xếp hạng performance trong tập peer (0–100).
     *
     * @param  list<int>|null  $peerUserIds  null = toàn bộ người dùng hệ thống
     */
    public function performancePercentileForUser(int $userId, ?array $peerUserIds = null): ?float
    {
        $scores = array_values($this->teamPerformanceDistribution($peerUserIds));
        if ($scores === []) {
            return null;
        }

        $mine = $this->personPerformancePercent($userId);
        $below = 0;
        foreach ($scores as $s) {
            if ($s < $mine) {
                $below++;
            }
        }

        $n = count($scores);
        if ($n <= 1) {
            return 100.0;
        }

        return round(($below / ($n - 1)) * 100, 2);
    }

    /**
     * Histogram buckets for charts (0–20, …, 80–100).
     *
     * @param  list<int>|null  $userIds
     * @return array<string, int>
     */
    public function teamPerformanceHistogram(?array $userIds = null): array
    {
        $buckets = [
            '0-20' => 0,
            '21-40' => 0,
            '41-60' => 0,
            '61-80' => 0,
            '81-100' => 0,
        ];

        foreach ($this->teamPerformanceDistribution($userIds) as $pct) {
            if ($pct <= 20) {
                $buckets['0-20']++;
            } elseif ($pct <= 40) {
                $buckets['21-40']++;
            } elseif ($pct <= 60) {
                $buckets['41-60']++;
            } elseif ($pct <= 80) {
                $buckets['61-80']++;
            } else {
                $buckets['81-100']++;
            }
        }

        return $buckets;
    }

    public function innovationFunnelCounts(): array
    {
        return [
            'submitted' => \App\Models\InnovationIdea::query()->where('status', 'submitted')->count(),
            'poc' => \App\Models\InnovationIdea::query()->where('status', 'poc')->count(),
            'applied' => \App\Models\InnovationIdea::query()->where('status', 'applied')->count(),
        ];
    }

    public function projectCsatAverage(Project $project): ?float
    {
        $avg = $project->csatResponses()->avg('rating');

        return $avg !== null ? round((float) $avg, 4) : null;
    }
}
