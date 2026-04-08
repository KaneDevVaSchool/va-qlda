<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Services\TaskProgressDisplayService;
use Illuminate\Http\Request;

class ProjectPhaseController extends Controller
{
    public function index(Project $project)
    {
        $this->authorize('view', $project);

        return $project->phases()->orderBy('sort_order')->orderBy('id')->get();
    }

    public function store(Request $request, Project $project)
    {
        $this->authorize('update', $project);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'progress_mode' => 'required|in:'.implode(',', TaskProgressDisplayService::MODES),
            'sort_order' => 'sometimes|integer|min:0|max:100000',
        ]);

        $phase = $project->phases()->create(array_merge($data, [
            'sort_order' => $data['sort_order'] ?? ((int) ($project->phases()->max('sort_order') ?? 0) + 1),
        ]));

        return response()->json($phase, 201);
    }

    public function update(Request $request, ProjectPhase $phase)
    {
        $this->authorize('update', $phase->project);

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'progress_mode' => 'sometimes|in:'.implode(',', TaskProgressDisplayService::MODES),
            'sort_order' => 'sometimes|integer|min:0|max:100000',
        ]);

        $phase->update($data);

        return response()->json($phase->fresh());
    }

    public function destroy(ProjectPhase $phase)
    {
        $this->authorize('update', $phase->project);
        $phase->delete();

        return response()->noContent();
    }
}
