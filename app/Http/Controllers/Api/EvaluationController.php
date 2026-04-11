<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Evaluation;
use App\Models\Project;
use App\Services\AuditLogger;
use App\Services\EvaluationScoring;
use App\Services\LeanEvaluationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class EvaluationController extends Controller
{
    public function index(Request $request)
    {
        $q = Evaluation::query()->with(['person:id,name', 'reviewer:id,name', 'project:id,name']);

        if (in_array($request->user()->role, ['developer'], true)) {
            $q->where('person_id', $request->user()->id);
        }

        if ($request->filled('project_id')) {
            $q->where('project_id', (int) $request->project_id);
        }

        return $q->orderByDesc('period_label')->paginate(25);
    }

    /**
     * Đánh giá Lean gắn với một dự án (từ màn chi tiết dự án).
     */
    public function forProject(Request $request, Project $project)
    {
        $q = Evaluation::query()
            ->where('project_id', $project->id)
            ->with(['person:id,name', 'reviewer:id,name']);

        if (in_array($request->user()->role, ['developer'], true)) {
            $q->where('person_id', $request->user()->id);
        }

        return $q->orderByDesc('period_label')->limit(50)->get();
    }

    public function show(Evaluation $evaluation)
    {
        $evaluation->load(['person:id,name', 'reviewer:id,name', 'peers.reviewer:id,name', 'project:id,name']);

        $extra = [];
        if ($evaluation->scoring_mode === 'lean' && $evaluation->lean_track && is_array($evaluation->criteria_scores)) {
            $extra['lean_computed'] = LeanEvaluationService::compute(
                $evaluation->lean_track,
                $evaluation->criteria_scores,
                (bool) $evaluation->kaizen_verified
            );
        }

        return response()->json(array_merge($evaluation->toArray(), $extra));
    }

    public function store(Request $request)
    {
        if (! in_array($request->user()->role, ['pm', 'tl', 'hr', 'admin'], true)) {
            abort(403);
        }

        $data = $request->validate([
            'period_type' => 'required|in:quarterly,annual',
            'period_label' => 'required|string|max:32',
            'person_id' => 'required|exists:users,id',
            'scoring_mode' => 'sometimes|in:legacy,lean',
            'lean_track' => 'nullable|in:dev,ba_pm_uiux',
            'career_level' => 'nullable|in:junior,middle,senior,lead',
            'project_id' => 'nullable|exists:projects,id',
            'criteria_scores' => 'nullable|array',
            'criteria_scores.*' => 'nullable|numeric|min:1|max:5',
            'kaizen_verified' => 'sometimes|boolean',
            'kaizen_action' => 'nullable|string|max:5000',
            'p1' => 'nullable|numeric|min:0|max:100',
            'p2' => 'nullable|numeric|min:0|max:100',
            'p3' => 'nullable|numeric|min:0|max:100',
            'status' => 'sometimes|in:draft,approved',
        ]);

        $mode = $data['scoring_mode'] ?? 'legacy';

        if (($data['status'] ?? 'draft') === 'approved') {
            return response()->json([
                'message' => 'Không thể tạo trực tiếp trạng thái approved; thêm peer và cập nhật sau.',
            ], 422);
        }

        $total = null;
        $grade = null;
        $kaizenVerified = false;
        $p1 = $data['p1'] ?? null;
        $p2 = $data['p2'] ?? null;
        $p3 = $data['p3'] ?? null;

        if ($mode === 'lean') {
            $request->validate([
                'lean_track' => 'required|in:dev,ba_pm_uiux',
                'criteria_scores' => 'required|array|min:1',
            ]);
            $scores = [];
            foreach ($data['criteria_scores'] ?? [] as $k => $v) {
                if ($v === null || $v === '') {
                    continue;
                }
                $scores[$k] = $v;
            }
            $kaizenVerified = $request->boolean('kaizen_verified');
            $computed = LeanEvaluationService::compute($data['lean_track'], $scores, $kaizenVerified);
            $p1 = $computed['p1'];
            $p2 = $computed['p2'];
            $p3 = $computed['p3'];
            $total = $computed['total'];
            $grade = $computed['grade'];
            $data['criteria_scores'] = $scores;
            if (count($scores) === 0) {
                return response()->json([
                    'message' => 'Cần ít nhất một tiêu chí có điểm (1–5) cho đánh giá Lean.',
                ], 422);
            }
        } elseif (isset($data['p1'], $data['p2'], $data['p3'])) {
            $total = EvaluationScoring::total((float) $data['p1'], (float) $data['p2'], (float) $data['p3']);
            $grade = EvaluationScoring::grade($total);
        }

        $evaluation = Evaluation::create([
            'period_type' => $data['period_type'],
            'period_label' => $data['period_label'],
            'scoring_mode' => $mode,
            'lean_track' => $mode === 'lean' ? $data['lean_track'] : null,
            'career_level' => $mode === 'lean' ? ($data['career_level'] ?? null) : null,
            'project_id' => $data['project_id'] ?? null,
            'person_id' => $data['person_id'],
            'p1' => $p1,
            'p2' => $p2,
            'p3' => $p3,
            'criteria_scores' => $mode === 'lean' ? ($data['criteria_scores'] ?? []) : null,
            'kaizen_verified' => $mode === 'lean' ? $kaizenVerified : false,
            'kaizen_action' => $mode === 'lean' ? ($data['kaizen_action'] ?? null) : null,
            'total' => $total,
            'grade' => $grade,
            'reviewer_id' => $request->user()->id,
            'status' => $data['status'] ?? 'draft',
        ]);

        AuditLogger::log('evaluation.created', $evaluation, null, $evaluation->toArray());

        return response()->json($evaluation->load(['person:id,name', 'reviewer:id,name', 'project:id,name']), 201);
    }

    public function update(Request $request, Evaluation $evaluation)
    {
        if (! in_array($request->user()->role, ['pm', 'tl', 'hr', 'admin'], true)) {
            abort(403);
        }

        if ($evaluation->status === 'approved') {
            abort(422, 'Evaluation is locked after approval.');
        }

        $before = $evaluation->getAttributes();
        $data = $request->validate([
            'project_id' => 'nullable|exists:projects,id',
            'criteria_scores' => 'nullable|array',
            'criteria_scores.*' => 'nullable|numeric|min:1|max:5',
            'kaizen_verified' => 'sometimes|boolean',
            'kaizen_action' => 'nullable|string|max:5000',
            'career_level' => 'nullable|in:junior,middle,senior,lead',
            'p1' => 'sometimes|numeric',
            'p2' => 'sometimes|numeric',
            'p3' => 'sometimes|numeric',
            'status' => 'sometimes|in:draft,approved',
            'adjustment_reason' => 'nullable|string',
            'adjustment_delta' => 'nullable|numeric|between:-5,5',
        ]);

        if (($data['status'] ?? null) === 'approved' && $evaluation->peers()->count() < 2) {
            return response()->json([
                'message' => 'BR-3P-03: Cần ít nhất 2 peer reviews trước khi duyệt.',
            ], 422);
        }

        if (array_key_exists('project_id', $data)) {
            $evaluation->project_id = $data['project_id'];
        }
        if (isset($data['criteria_scores']) && $evaluation->scoring_mode === 'lean') {
            $scores = [];
            foreach ($data['criteria_scores'] as $k => $v) {
                if ($v === null || $v === '') {
                    continue;
                }
                $scores[$k] = $v;
            }
            $evaluation->criteria_scores = $scores;
        }

        foreach (['kaizen_verified', 'kaizen_action', 'career_level'] as $k) {
            if (array_key_exists($k, $data)) {
                $evaluation->{$k} = $data[$k];
            }
        }

        foreach (['p1', 'p2', 'p3', 'status', 'adjustment_reason', 'adjustment_delta'] as $k) {
            if (array_key_exists($k, $data)) {
                $evaluation->{$k} = $data[$k];
            }
        }

        if ($evaluation->scoring_mode === 'legacy'
            && $evaluation->p1 !== null && $evaluation->p2 !== null && $evaluation->p3 !== null) {
            $base = EvaluationScoring::total(
                (float) $evaluation->p1,
                (float) $evaluation->p2,
                (float) $evaluation->p3
            );
            $evaluation->total = $base + (float) ($evaluation->adjustment_delta ?? 0);
            $evaluation->total = max(0, min(100, (float) $evaluation->total));
            $evaluation->grade = EvaluationScoring::grade((float) $evaluation->total);
        }

        if ($evaluation->scoring_mode === 'lean' && $evaluation->lean_track && is_array($evaluation->criteria_scores)) {
            $computed = LeanEvaluationService::compute(
                $evaluation->lean_track,
                $evaluation->criteria_scores,
                (bool) $evaluation->kaizen_verified
            );
            $evaluation->p1 = $computed['p1'];
            $evaluation->p2 = $computed['p2'];
            $evaluation->p3 = $computed['p3'];
            $adj = (float) ($evaluation->adjustment_delta ?? 0);
            if ($computed['total'] !== null) {
                $evaluation->total = max(1.0, min(5.0, $computed['total'] + $adj));
                $evaluation->grade = LeanEvaluationService::gradeLean((float) $evaluation->total);
            }
        }

        $evaluation->save();

        AuditLogger::log('evaluation.updated', $evaluation, $before, $evaluation->getAttributes());

        return $evaluation->fresh()->load(['person:id,name', 'reviewer:id,name', 'project:id,name']);
    }

    public function exportPdf(Request $request, Evaluation $evaluation)
    {
        if ((int) $evaluation->person_id !== (int) $request->user()->id
            && ! in_array($request->user()->role, ['admin', 'pm', 'tl', 'hr'], true)) {
            abort(403);
        }

        $evaluation->load(['person:id,name,email', 'reviewer:id,name', 'peers.reviewer:id,name']);

        $pdf = Pdf::loadView('reports.evaluation-3p', ['e' => $evaluation]);

        return $pdf->download('ppms-3p-evaluation-'.$evaluation->id.'.pdf');
    }
}
