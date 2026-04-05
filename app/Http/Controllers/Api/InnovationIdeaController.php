<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InnovationIdea;
use App\Services\AuditLogger;
use Illuminate\Http\Request;

class InnovationIdeaController extends Controller
{
    public function index(Request $request)
    {
        $q = InnovationIdea::query()->with(['submitter:id,name', 'project:id,name']);

        if ($request->filled('status')) {
            $q->where('status', $request->query('status'));
        }

        return $q->orderByDesc('id')->paginate(30);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'project_id' => 'nullable|exists:projects,id',
        ]);

        $idea = InnovationIdea::query()->create(array_merge($data, [
            'submitter_id' => $request->user()->id,
            'status' => 'submitted',
        ]));

        AuditLogger::log('innovation.created', $idea, null, $idea->toArray());

        return response()->json($idea->load(['submitter:id,name']), 201);
    }

    public function updateStatus(Request $request, InnovationIdea $idea)
    {
        if (! in_array($request->user()->role, ['admin', 'pm', 'tl'], true)) {
            abort(403);
        }

        $data = $request->validate([
            'status' => 'required|in:submitted,poc,applied',
        ]);

        $before = $idea->getAttributes();
        $idea->update($data);
        AuditLogger::log('innovation.status', $idea, $before, $idea->getAttributes());

        return $idea->fresh();
    }
}
