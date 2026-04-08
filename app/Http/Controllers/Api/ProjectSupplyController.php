<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectSupply;
use Illuminate\Http\Request;

class ProjectSupplyController extends Controller
{
    public function index(Project $project)
    {
        $this->authorize('view', $project);

        return $project->supplies()->orderBy('sort_order')->orderBy('id')->get();
    }

    public function store(Request $request, Project $project)
    {
        $this->authorize('update', $project);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'quantity' => 'sometimes|numeric|min:0',
            'unit' => 'sometimes|string|max:32',
            'notes' => 'nullable|string',
            'sort_order' => 'sometimes|integer|min:0|max:100000',
        ]);

        $row = $project->supplies()->create(array_merge($data, [
            'quantity' => $data['quantity'] ?? 0,
            'unit' => $data['unit'] ?? '',
            'sort_order' => $data['sort_order'] ?? ($project->supplies()->max('sort_order') + 1),
        ]));

        return response()->json($row, 201);
    }

    public function update(Request $request, ProjectSupply $supply)
    {
        $this->authorize('update', $supply->project);

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'quantity' => 'sometimes|numeric|min:0',
            'unit' => 'sometimes|string|max:32',
            'notes' => 'nullable|string',
            'sort_order' => 'sometimes|integer|min:0|max:100000',
        ]);

        $supply->update($data);

        return response()->json($supply->fresh());
    }

    public function destroy(ProjectSupply $supply)
    {
        $this->authorize('update', $supply->project);
        $supply->delete();

        return response()->noContent();
    }
}
