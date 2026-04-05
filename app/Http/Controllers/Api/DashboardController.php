<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kaizen;
use App\Models\KpiSnapshot;
use App\Models\Project;
use App\Models\User;
use App\Services\KpiEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function summary(Request $request, KpiEngine $kpiEngine)
    {
        $typeDistribution = Project::query()
            ->whereNull('archived_at')
            ->select('type', DB::raw('count(*) as count'))
            ->groupBy('type')
            ->pluck('count', 'type');

        $statusDistribution = Project::query()
            ->whereNull('archived_at')
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        $phaseDistribution = Project::query()
            ->whereNull('archived_at')
            ->select('phase', DB::raw('count(*) as count'))
            ->groupBy('phase')
            ->pluck('count', 'phase');

        $kaizenByStatus = Kaizen::query()
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        $workload = DB::table('tasks')
            ->join('users', 'users.id', '=', 'tasks.assignee_id')
            ->whereNotNull('tasks.assignee_id')
            ->where('tasks.status', '!=', 'done')
            ->groupBy('tasks.assignee_id', 'users.name')
            ->select('tasks.assignee_id', 'users.name as assignee_name', DB::raw('count(*) as open_tasks'))
            ->orderByDesc('open_tasks')
            ->get();

        $atRiskProjects = Project::query()
            ->whereNull('archived_at')
            ->whereIn('status', ['at_risk', 'delayed', 'blocked'])
            ->with('owner:id,name')
            ->orderByDesc('updated_at')
            ->limit(10)
            ->get(['id', 'name', 'type', 'status', 'progress', 'owner_id', 'deadline']);

        $kpiTrend = KpiSnapshot::query()
            ->where('entity_type', (new User)->getMorphClass())
            ->where('entity_id', $request->user()->id)
            ->where('metric_name', 'performance_pct')
            ->orderBy('week_ending')
            ->limit(12)
            ->get(['week_ending', 'value']);

        return response()->json([
            'projects' => [
                'by_type' => $typeDistribution,
                'by_status' => $statusDistribution,
                'by_phase' => $phaseDistribution,
            ],
            'kaizen_by_status' => $kaizenByStatus,
            'workload_open_tasks' => $workload,
            'at_risk_projects' => $atRiskProjects,
            'kpi_performance_trend' => $kpiTrend,
            'innovation_funnel' => $kpiEngine->innovationFunnelCounts(),
        ]);
    }
}
