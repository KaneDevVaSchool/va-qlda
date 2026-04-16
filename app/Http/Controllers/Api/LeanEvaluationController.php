<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Evaluation;
use App\Models\Kaizen;
use App\Models\Task;
use App\Models\User;
use App\Services\LeanEvaluationService;
use App\Services\LeanFrameworkConfigService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LeanEvaluationController extends Controller
{
    /**
     * Khung Lean 3P + rubric (payload JSON/DB + i18n).
     */
    public function preview(Request $request)
    {
        $data = $request->validate([
            'lean_track' => 'required|in:dev,ba_pm_uiux',
            'criteria_scores' => 'required|array',
            'criteria_scores.*' => 'nullable|numeric|min:1|max:5',
            'kaizen_verified' => 'sometimes|boolean',
        ]);
        $scores = [];
        foreach ($data['criteria_scores'] as $k => $v) {
            if ($v === null || $v === '') {
                continue;
            }
            $scores[$k] = $v;
        }

        $kaizenVerified = $request->boolean('kaizen_verified');

        return response()->json(LeanEvaluationService::compute($data['lean_track'], $scores, $kaizenVerified));
    }

    public function framework(LeanFrameworkConfigService $leanFramework)
    {
        $fw = $leanFramework->payload();

        return response()->json([
            'version' => $fw['version'] ?? null,
            'pillar_weights' => $fw['pillar_weights'] ?? [],
            'grade_bands' => $fw['grade_bands'] ?? [],
            'grade_scale_legend' => $fw['grade_scale_legend'] ?? [],
            'tracks' => $fw['tracks'] ?? [],
            'tracks_resolved' => [
                'dev' => LeanEvaluationService::criteriaForTrack('dev'),
                'ba_pm_uiux' => LeanEvaluationService::criteriaForTrack('ba_pm_uiux'),
            ],
            'radar_axes' => $fw['radar_axes'] ?? [],
            'extras' => $fw['extras'] ?? [],
        ]);
    }

    /**
     * Bối cảnh Kaizen trong hệ thống (gợi ý khi chấm & xác nhận).
     */
    public function kaizenContext(Request $request)
    {
        $request->validate([
            'person_id' => 'required|exists:cms.users,id',
            'period_label' => 'nullable|string|max:32',
        ]);

        $q = Kaizen::query()->where('submitter_id', (int) $request->person_id);
        $range = self::quarterRangeFromLabel($request->period_label);
        if ($range !== null) {
            $q->whereBetween('week_start', [$range[0]->toDateString(), $range[1]->toDateString()]);
        }

        $verified = (clone $q)->whereIn('status', ['approved', 'implemented', 'verified'])->count();
        $totalInScope = (clone $q)->count();

        return response()->json([
            'kaizens_verified_count' => $verified,
            'kaizens_total_in_scope' => $totalInScope,
            'period_label' => $request->period_label,
            'quarter_range_applied' => $range !== null,
            'hint' => 'Kaizen đã xác nhận (TL/PM) hỗ trợ đạt A/S; đồng bộ với checkbox Kaizen trong đánh giá.',
        ]);
    }

    /**
     * Dashboard tổng hợp — xếp hạng, phân bố grade, heatmap theo kỳ.
     */
    public function teamDashboard(Request $request)
    {
        if (! in_array($request->user()->role, ['admin', 'pm', 'tl', 'hr'], true)) {
            abort(403);
        }

        $request->validate([
            'period_label' => 'nullable|string|max:32',
        ]);

        $periodFilter = $request->query('period_label');

        $q = Evaluation::query()
            ->where('scoring_mode', 'lean')
            ->with(['person:id,name']);

        if ($periodFilter) {
            $q->where('period_label', $periodFilter);
        }

        $evals = $q->orderByDesc('total')->limit(500)->get();

        $ranking = $evals->values()->map(function (Evaluation $e, int $i) {
            return [
                'rank' => $i + 1,
                'evaluation_id' => $e->id,
                'person_id' => $e->person_id,
                'name' => $e->person->name ?? null,
                'career_level' => $e->career_level,
                'lean_track' => $e->lean_track,
                'period_label' => $e->period_label,
                'p1' => $e->p1 !== null ? (float) $e->p1 : null,
                'p2' => $e->p2 !== null ? (float) $e->p2 : null,
                'p3' => $e->p3 !== null ? (float) $e->p3 : null,
                'total' => $e->total !== null ? (float) $e->total : null,
                'grade' => $e->grade,
                'kaizen_verified' => (bool) $e->kaizen_verified,
            ];
        });

        $distribution = $evals->groupBy('grade')->map->count()->all();

        $periods = Evaluation::query()
            ->where('scoring_mode', 'lean')
            ->distinct()
            ->orderByDesc('period_label')
            ->limit(6)
            ->pluck('period_label')
            ->values()
            ->all();

        $personIds = $evals->pluck('person_id')->unique()->values();
        $heatmap = [];
        foreach ($personIds as $pid) {
            $name = optional(User::query()->find($pid))->name;
            $cells = [];
            foreach ($periods as $pl) {
                $cell = Evaluation::query()
                    ->where('scoring_mode', 'lean')
                    ->where('person_id', $pid)
                    ->where('period_label', $pl)
                    ->orderByDesc('id')
                    ->first();
                $cells[$pl] = $cell && $cell->total !== null ? round((float) $cell->total, 2) : null;
            }
            $heatmap[] = [
                'person_id' => $pid,
                'name' => $name,
                'cells' => $cells,
            ];
        }

        return response()->json([
            'period_filter' => $periodFilter,
            'period_columns' => $periods,
            'ranking' => $ranking,
            'grade_distribution' => $distribution,
            'heatmap' => $heatmap,
            'count' => $evals->count(),
        ]);
    }

    /**
     * @return array{0: \Carbon\Carbon, 1: \Carbon\Carbon}|null
     */
    private static function quarterRangeFromLabel(?string $periodLabel): ?array
    {
        if (! $periodLabel || ! preg_match('/^(\d{4})-Q([1-4])$/', $periodLabel, $m)) {
            return null;
        }
        $y = (int) $m[1];
        $q = (int) $m[2];
        $startMonth = ($q - 1) * 3 + 1;
        $start = Carbon::createFromDate($y, $startMonth, 1)->startOfDay();
        $end = $start->copy()->addMonths(3)->subDay()->endOfDay();

        return [$start, $end];
    }

    /**
     * Bối cảnh task của nhân sự (gợi ý cho P3 — Performance), có thể lọc theo dự án.
     */
    public function taskContext(Request $request)
    {
        $request->validate([
            'person_id' => 'required|exists:cms.users,id',
            'project_id' => 'nullable|exists:projects,id',
        ]);

        $q = Task::query()->where('assignee_id', (int) $request->person_id);
        if ($request->filled('project_id')) {
            $q->where('project_id', (int) $request->project_id);
        }

        $total = (clone $q)->count();
        $done = (clone $q)->where('status', 'done')->count();
        $open = (clone $q)->where('status', '!=', 'done')->count();

        $today = now()->startOfDay();
        $overdue = (clone $q)->where('status', '!=', 'done')
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', $today)
            ->count();

        $doneWithDue = (clone $q)->where('status', 'done')->whereNotNull('due_date')->get(['due_date', 'updated_at']);
        $onTime = 0;
        foreach ($doneWithDue as $t) {
            if ($t->updated_at && $t->due_date && $t->updated_at->lte($t->due_date->endOfDay())) {
                $onTime++;
            }
        }
        $n = $doneWithDue->count();
        $on_time_rate = $n > 0 ? round($onTime * 100 / $n, 1) : null;

        return response()->json([
            'scope' => $request->filled('project_id') ? 'project' : 'all',
            'tasks_total' => $total,
            'tasks_done' => $done,
            'tasks_open' => $open,
            'tasks_overdue_open' => $overdue,
            'on_time_done_rate_pct' => $on_time_rate,
            'hint' => 'Dùng làm tham chiếu khi chấm P3 (tiến độ / chất lượng giao hàng).',
        ]);
    }
}
