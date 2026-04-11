<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InnovationIdea;
use App\Models\Kaizen;
use App\Models\Project;
use App\Models\Task;
use App\Support\MigrationCms;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function summary(Request $request)
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

        return response()->json([
            'projects' => [
                'by_type' => $typeDistribution,
                'by_status' => $statusDistribution,
                'by_phase' => $phaseDistribution,
            ],
            'kaizen_by_status' => $kaizenByStatus,
            'workload_open_tasks' => $workload,
            'at_risk_projects' => $atRiskProjects,
            'innovation_funnel' => [
                'submitted' => InnovationIdea::query()->where('status', 'submitted')->count(),
                'poc' => InnovationIdea::query()->where('status', 'poc')->count(),
                'applied' => InnovationIdea::query()->where('status', 'applied')->count(),
            ],
            'task_analytics' => $this->taskAnalytics(),
            'board_stats' => $this->boardStats(),
            'projects_by_owner' => $this->projectsByOwner(),
        ]);
    }

    /**
     * Chỉ số dạng board (widget hàng đầu / lưới) — theo mô hình tổng hợp công ty.
     *
     * @return array<string, int>
     */
    private function boardStats(): array
    {
        $tasksBase = Task::query()->whereHas('project', function ($q) {
            $q->whereNull('archived_at');
        });

        $startMonth = now()->startOfMonth();

        $projectsBase = Project::query()->whereNull('archived_at');

        return [
            'tasks_in_progress' => (int) (clone $tasksBase)->where('status', 'in_progress')->count(),
            'tasks_this_month' => (int) (clone $tasksBase)->where('created_at', '>=', $startMonth)->count(),
            'projects_unfinished' => (int) (clone $projectsBase)->where('phase', '!=', 'done')->count(),
            'tasks_due_today' => (int) (clone $tasksBase)
                ->whereDate('due_date', now()->toDateString())
                ->where('status', '!=', 'done')
                ->count(),
            'projects_new_month' => (int) (clone $projectsBase)->where('created_at', '>=', $startMonth)->count(),
            'projects_phase_done' => (int) (clone $projectsBase)->where('phase', 'done')->count(),
            'projects_active' => (int) (clone $projectsBase)->where('phase', '!=', 'done')->count(),
            'projects_blocked' => (int) (clone $projectsBase)->where('status', 'blocked')->count(),
        ];
    }

    /**
     * Top PM theo số dự án (thay thế “theo phòng ban” khi chưa có org unit).
     *
     * @return list<array{owner_id: int, name: string, count: int}>
     */
    private function projectsByOwner(): array
    {
        $rows = DB::table('projects')
            ->join(MigrationCms::usersJoinTarget(), 'users.id', '=', 'projects.owner_id')
            ->whereNull('projects.archived_at')
            ->whereNotNull('projects.owner_id')
            ->groupBy('projects.owner_id', 'users.name')
            ->select('projects.owner_id as owner_id', 'users.name as name', DB::raw('count(*) as c'))
            ->orderByDesc('c')
            ->limit(14)
            ->get();

        return $rows->map(fn ($r) => [
            'owner_id' => (int) $r->owner_id,
            'name' => (string) $r->name,
            'count' => (int) $r->c,
        ])->values()->all();
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
