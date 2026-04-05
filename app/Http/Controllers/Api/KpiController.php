<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KpiSnapshot;
use App\Services\KpiEngine;
use App\Services\KpiWeeklySnapshotService;
use Illuminate\Http\Request;

class KpiController extends Controller
{
    public function __construct(
        protected KpiEngine $kpiEngine,
        protected KpiWeeklySnapshotService $snapshotService
    ) {}

    public function current(Request $request)
    {
        $user = $request->user();
        $teamAvg = $this->kpiEngine->teamAveragePerformance();
        $perf = $this->kpiEngine->personPerformancePercent((int) $user->id);
        $eff = $this->kpiEngine->personEfficiencyRatio((int) $user->id);
        $sla = $this->kpiEngine->maintenanceSlaRateForUser((int) $user->id);

        return response()->json([
            'person' => [
                'performance_pct' => $perf,
                'efficiency_ratio' => $eff,
                'efficiency_warn' => $eff !== null && ($eff < 0.7 || $eff > 1.5),
                'sla_rate_pct' => $sla,
                'sla_critical' => $sla !== null && $sla < 80,
                'benchmark_team_avg_performance' => $teamAvg,
                'performance_percentile' => $this->kpiEngine->performancePercentileForUser((int) $user->id),
                'team_histogram' => $this->kpiEngine->teamPerformanceHistogram(),
            ],
            'innovation_funnel' => $this->kpiEngine->innovationFunnelCounts(),
        ]);
    }

    /**
     * BR-KPI-07: benchmark chi tiết (phân bổ team + vị trí cá nhân).
     */
    public function benchmark(Request $request)
    {
        $user = $request->user();
        $dist = $this->kpiEngine->teamPerformanceDistribution();

        return response()->json([
            'your_performance_pct' => $this->kpiEngine->personPerformancePercent((int) $user->id),
            'your_percentile' => $this->kpiEngine->performancePercentileForUser((int) $user->id),
            'team_avg' => $this->kpiEngine->teamAveragePerformance(),
            'histogram' => $this->kpiEngine->teamPerformanceHistogram(),
            'team_scores' => $dist,
        ]);
    }

    public function snapshots(Request $request)
    {
        $q = KpiSnapshot::query()->orderByDesc('week_ending')->orderBy('metric_name');

        if ($request->filled('week_ending')) {
            $q->whereDate('week_ending', $request->query('week_ending'));
        }
        if ($request->filled('entity_type')) {
            $q->where('entity_type', $request->query('entity_type'));
        }
        if ($request->filled('entity_id')) {
            $q->where('entity_id', (int) $request->query('entity_id'));
        }

        return $q->paginate(100);
    }

    public function runSnapshot(Request $request)
    {
        if (! in_array($request->user()->role, ['admin', 'pm'], true)) {
            abort(403);
        }

        $this->snapshotService->run();

        return response()->json(['ok' => true, 'week_ending' => $this->snapshotService->weekEndingDate()->toDateString()]);
    }
}
