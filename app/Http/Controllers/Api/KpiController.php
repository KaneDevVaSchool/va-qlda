<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KpiSnapshot;
use App\Models\Team;
use App\Models\User;
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
        $peerIds = $this->resolveTeamPeerUserIds($request, $user);
        $teamMeta = $this->teamScopeMeta($request);

        $teamAvg = $this->kpiEngine->teamAveragePerformance($peerIds);
        $perf = $this->kpiEngine->personPerformancePercent((int) $user->id);
        $eff = $this->kpiEngine->personEfficiencyRatio((int) $user->id);
        $sla = $this->kpiEngine->maintenanceSlaRateForUser((int) $user->id);

        return response()->json([
            'scope' => $teamMeta,
            'person' => [
                'performance_pct' => $perf,
                'efficiency_ratio' => $eff,
                'efficiency_warn' => $eff !== null && ($eff < 0.7 || $eff > 1.5),
                'sla_rate_pct' => $sla,
                'sla_critical' => $sla !== null && $sla < 80,
                'benchmark_team_avg_performance' => $teamAvg,
                'performance_percentile' => $this->kpiEngine->performancePercentileForUser((int) $user->id, $peerIds),
                'team_histogram' => $this->kpiEngine->teamPerformanceHistogram($peerIds),
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
        $peerIds = $this->resolveTeamPeerUserIds($request, $user);
        $dist = $this->kpiEngine->teamPerformanceDistribution($peerIds);

        return response()->json([
            'scope' => $this->teamScopeMeta($request),
            'your_performance_pct' => $this->kpiEngine->personPerformancePercent((int) $user->id),
            'your_percentile' => $this->kpiEngine->performancePercentileForUser((int) $user->id, $peerIds),
            'team_avg' => $this->kpiEngine->teamAveragePerformance($peerIds),
            'histogram' => $this->kpiEngine->teamPerformanceHistogram($peerIds),
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

    /**
     * @return list<int>|null null = toàn tổ chức
     */
    protected function resolveTeamPeerUserIds(Request $request, User $user): ?array
    {
        if (! $request->filled('team_id')) {
            return null;
        }

        $team = Team::query()->with('members')->findOrFail((int) $request->query('team_id'));
        $this->assertUserCanAccessTeam($user, $team);

        return $team->members->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
    }

    /**
     * @return array{team_id: int|null, team_name: string|null, mode: string}
     */
    protected function teamScopeMeta(Request $request): array
    {
        if (! $request->filled('team_id')) {
            return [
                'team_id' => null,
                'team_name' => null,
                'mode' => 'organization',
            ];
        }

        $team = Team::query()->find((int) $request->query('team_id'));

        return [
            'team_id' => $team?->id,
            'team_name' => $team?->name,
            'mode' => 'team',
        ];
    }

    protected function assertUserCanAccessTeam(User $user, Team $team): void
    {
        if (in_array($user->role, ['admin', 'pm'], true)) {
            return;
        }

        $isMember = $team->members()->where('user_id', $user->id)->exists();
        $isCreator = (int) $team->created_by === (int) $user->id;

        if (! $isMember && ! $isCreator) {
            abort(403, 'Bạn không thuộc team này.');
        }
    }
}
