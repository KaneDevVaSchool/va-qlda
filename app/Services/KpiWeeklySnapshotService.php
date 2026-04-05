<?php

namespace App\Services;

use App\Models\KpiSnapshot;
use App\Models\Project;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class KpiWeeklySnapshotService
{
    public function __construct(
        protected KpiEngine $kpiEngine
    ) {}

    public function weekEndingDate(?Carbon $at = null): Carbon
    {
        $at = $at ?? Carbon::now();

        return $at->copy()->endOfWeek(Carbon::SUNDAY)->startOfDay();
    }

    public function run(?Carbon $at = null): void
    {
        $weekEnding = $this->weekEndingDate($at);
        $teamAvg = $this->kpiEngine->teamAveragePerformance();

        DB::transaction(function () use ($weekEnding, $teamAvg) {
            Project::query()
                ->whereNull('archived_at')
                ->each(function (Project $p) use ($weekEnding) {
                    $atRisk = $p->tasks()->where(function ($q) {
                        $q->where('status', 'blocked')
                            ->orWhere(function ($q2) {
                                $q2->whereNotNull('due_date')
                                    ->whereDate('due_date', '<', now()->toDateString())
                                    ->whereNotIn('status', ['done']);
                            });
                    })->count();
                    $this->putSnapshot($p, $weekEnding, 'progress', (float) $p->progress, []);
                    $this->putSnapshot($p, $weekEnding, 'at_risk_task_count', (float) $atRisk, [
                        'status' => $p->status,
                    ]);
                });

            User::query()->each(function (User $u) use ($weekEnding, $teamAvg) {
                $perf = $this->kpiEngine->personPerformancePercent((int) $u->id);
                $eff = $this->kpiEngine->personEfficiencyRatio((int) $u->id);
                $sla = $this->kpiEngine->maintenanceSlaRateForUser((int) $u->id);

                $this->putSnapshot($u, $weekEnding, 'performance_pct', $perf, [
                    'benchmark_team_avg' => $teamAvg,
                ]);
                if ($eff !== null) {
                    $this->putSnapshot($u, $weekEnding, 'efficiency_ratio', $eff, [
                        'warn_low' => $eff < 0.7,
                        'warn_high' => $eff > 1.5,
                    ]);
                }
                if ($sla !== null) {
                    $this->putSnapshot($u, $weekEnding, 'sla_rate_pct', $sla, [
                        'critical' => $sla < 80,
                    ]);
                }

                $kaizenCount = $u->kaizens()
                    ->whereBetween('week_start', [
                        $weekEnding->copy()->subDays(6),
                        $weekEnding,
                    ])
                    ->count();
                $this->putSnapshot($u, $weekEnding, 'kaizen_count_week', (float) $kaizenCount, []);
            });
        });
    }

    /**
     * BR-KPI-06: snapshot immutable — không ghi đè bản ghi cùng tuần/metric/entity.
     */
    protected function putSnapshot($entity, Carbon $weekEnding, string $metric, float $value, array $meta): void
    {
        $exists = KpiSnapshot::query()->where([
            'week_ending' => $weekEnding->toDateString(),
            'entity_type' => $entity->getMorphClass(),
            'entity_id' => $entity->getKey(),
            'metric_name' => $metric,
        ])->exists();

        if ($exists) {
            return;
        }

        KpiSnapshot::query()->create([
            'week_ending' => $weekEnding->toDateString(),
            'entity_type' => $entity->getMorphClass(),
            'entity_id' => $entity->getKey(),
            'metric_name' => $metric,
            'value' => $value,
            'meta' => $meta ?: null,
        ]);
    }
}
