<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Evaluation;
use App\Services\AuditLogger;
use App\Services\EvaluationScoring;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class EvaluationController extends Controller
{
    public function index(Request $request)
    {
        $q = Evaluation::query()->with(['person:id,name', 'reviewer:id,name']);

        if (in_array($request->user()->role, ['developer'], true)) {
            $q->where('person_id', $request->user()->id);
        }

        return $q->orderByDesc('period_label')->paginate(25);
    }

    public function show(Evaluation $evaluation)
    {
        $evaluation->load(['person:id,name', 'reviewer:id,name', 'peers.reviewer:id,name']);

        return $evaluation;
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
            'p1' => 'nullable|numeric|min:0|max:100',
            'p2' => 'nullable|numeric|min:0|max:100',
            'p3' => 'nullable|numeric|min:0|max:100',
            'status' => 'sometimes|in:draft,approved',
        ]);

        if (($data['status'] ?? 'draft') === 'approved') {
            return response()->json([
                'message' => 'Không thể tạo trực tiếp trạng thái approved; thêm peer và cập nhật sau.',
            ], 422);
        }

        $total = null;
        $grade = null;
        if (isset($data['p1'], $data['p2'], $data['p3'])) {
            $total = EvaluationScoring::total((float) $data['p1'], (float) $data['p2'], (float) $data['p3']);
            $grade = EvaluationScoring::grade($total);
        }

        $evaluation = Evaluation::create(array_merge($data, [
            'total' => $total,
            'grade' => $grade,
            'reviewer_id' => $request->user()->id,
            'status' => $data['status'] ?? 'draft',
        ]));

        AuditLogger::log('evaluation.created', $evaluation, null, $evaluation->toArray());

        return response()->json($evaluation->load(['person:id,name', 'reviewer:id,name']), 201);
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
            'p1' => 'sometimes|numeric|min:0|max:100',
            'p2' => 'sometimes|numeric|min:0|max:100',
            'p3' => 'sometimes|numeric|min:0|max:100',
            'status' => 'sometimes|in:draft,approved',
            'adjustment_reason' => 'nullable|string',
            'adjustment_delta' => 'nullable|numeric|between:-5,5',
        ]);

        if (($data['status'] ?? null) === 'approved' && $evaluation->peers()->count() < 2) {
            return response()->json([
                'message' => 'BR-3P-03: Cần ít nhất 2 peer reviews trước khi duyệt.',
            ], 422);
        }

        $evaluation->fill($data);

        if ($evaluation->p1 !== null && $evaluation->p2 !== null && $evaluation->p3 !== null) {
            $base = EvaluationScoring::total(
                (float) $evaluation->p1,
                (float) $evaluation->p2,
                (float) $evaluation->p3
            );
            $evaluation->total = $base + (float) ($evaluation->adjustment_delta ?? 0);
            $evaluation->total = max(0, min(100, $evaluation->total));
            $evaluation->grade = EvaluationScoring::grade((float) $evaluation->total);
        }

        $evaluation->save();

        AuditLogger::log('evaluation.updated', $evaluation, $before, $evaluation->getAttributes());

        return $evaluation->fresh()->load(['person:id,name', 'reviewer:id,name']);
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
