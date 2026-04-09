<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kaizen;
use App\Models\KpiSnapshot;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\KpiEngine;
use App\Support\MigrationCms;
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
            ->join(MigrationCms::usersJoinTarget(), 'users.id', '=', 'tasks.assignee_id')
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
            'task_analytics' => $this->taskAnalytics(),
        ]);
    }

    /**
     * Thống kê công việc toàn hệ (dự án chưa lưu trữ) — phục vụ tab Báo cáo công việc trên dashboard.
     *
     * @return array<string, mixed>
     */
    private function taskAnalytics(): array
    {
        $tasksBase = Task::query()->whereHas('project', function ($q) {
            $q->whereNull('archived_at');
        });

        $tasksByStatus = (clone $tasksBase)
            ->selectRaw('status, count(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');

        $tasksTotal = (clone $tasksBase)->count();
        $tasksDone = (clone $tasksBase)->where('status', 'done')->count();

        $hoursAll = (clone $tasksBase)
            ->selectRaw('coalesce(sum(estimate_hours), 0) as est, coalesce(sum(actual_hours), 0) as act')
            ->first();
        $hoursDone = (clone $tasksBase)->where('status', 'done')
            ->selectRaw('coalesce(sum(estimate_hours), 0) as est, coalesce(sum(actual_hours), 0) as act')
            ->first();

        $progressBuckets = ['0-25' => 0, '25-50' => 0, '50-75' => 0, '75-100' => 0];
        foreach (Project::query()->whereNull('archived_at')->pluck('progress') as $p) {
            $v = (float) $p;
            if ($v < 25) {
                $progressBuckets['0-25']++;
            } elseif ($v < 50) {
                $progressBuckets['25-50']++;
            } elseif ($v < 75) {
                $progressBuckets['50-75']++;
            } else {
                $progressBuckets['75-100']++;
            }
        }

        $tasksByCategory = (clone $tasksBase)
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->selectRaw('category, count(*) as c')
            ->groupBy('category')
            ->orderByDesc('c')
            ->limit(12)
            ->pluck('c', 'category');

        return [
            'tasks_by_status' => $tasksByStatus,
            'tasks_total' => $tasksTotal,
            'tasks_done' => $tasksDone,
            'completion_pct' => $tasksTotal > 0 ? round($tasksDone * 100 / $tasksTotal, 1) : 0.0,
            'hours' => [
                'all' => [
                    'estimate' => (float) ($hoursAll->est ?? 0),
                    'actual' => (float) ($hoursAll->act ?? 0),
                ],
                'done' => [
                    'estimate' => (float) ($hoursDone->est ?? 0),
                    'actual' => (float) ($hoursDone->act ?? 0),
                ],
            ],
            'project_progress_buckets' => $progressBuckets,
            'tasks_by_category' => $tasksByCategory,
        ];
    }
}
