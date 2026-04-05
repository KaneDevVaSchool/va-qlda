<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;

class KpiEngine
{
    /**
     * BR-KPI-01 Performance = Σ(Completed Weight) / Σ(Planned Weight) × 100%
     */
    public function personPerformancePercent(int $userId): float
    {
        $q = Task::query()->where('assignee_id', $userId);

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
        $q = Task::query()
            ->where('assignee_id', $userId)
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

    public function teamAveragePerformance(): float
    {
        $dist = $this->teamPerformanceDistribution();

        return count($dist) > 0 ? round(array_sum($dist) / count($dist), 4) : 0.0;
    }

    /**
     * @return array<int, float> user_id => performance %
     */
    public function teamPerformanceDistribution(): array
    {
        $out = [];
        foreach (User::query()->pluck('id') as $id) {
            $out[(int) $id] = $this->personPerformancePercent((int) $id);
        }

        return $out;
    }

    /**
     * BR-KPI-07: percentile xếp hạng performance trong team (0–100).
     */
    public function performancePercentileForUser(int $userId): ?float
    {
        $scores = array_values($this->teamPerformanceDistribution());
        if ($scores === []) {
            return null;
        }

        sort($scores);
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
     * @return array<string, int>
     */
    public function teamPerformanceHistogram(): array
    {
        $buckets = [
            '0-20' => 0,
            '21-40' => 0,
            '41-60' => 0,
            '61-80' => 0,
            '81-100' => 0,
        ];

        foreach ($this->teamPerformanceDistribution() as $pct) {
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
