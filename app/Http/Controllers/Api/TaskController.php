<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use App\Services\AuditLogger;
use App\Services\ProjectProgressService;
use App\Services\TaskWeightCalculator;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function __construct(
        protected ProjectProgressService $progressService
    ) {}

    public function index(Project $project)
    {
        return $project->tasks()
            ->with([
                'assignee:id,name,email',
                'children',
                'predecessors:id,name,status',
            ])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    public function store(Request $request, Project $project)
    {
        $data = $request->validate([
            'parent_id' => 'nullable|exists:tasks,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'assignee_id' => 'nullable|exists:users,id',
            'estimate_hours' => 'required|numeric|min:0',
            'actual_hours' => 'sometimes|numeric|min:0',
            'complexity' => 'required|integer|min:1|max:5',
            'impact' => 'required|integer|min:1|max:5',
            'due_date' => 'nullable|date',
            'status' => 'sometimes|in:todo,in_progress,done,blocked',
        ]);

        if (! empty($data['parent_id'])) {
            $this->assertParentInProjectAndDepth((int) $data['parent_id'], $project->id);
        }

        $weight = TaskWeightCalculator::compute((int) $data['complexity'], (int) $data['impact']);

        $task = $project->tasks()->create(array_merge($data, [
            'weight' => $weight,
            'status' => $data['status'] ?? 'todo',
            'actual_hours' => $data['actual_hours'] ?? 0,
        ]));

        $this->progressService->syncProjectProgress($project->fresh());
        AuditLogger::log('task.created', $task, null, $task->toArray());

        return response()->json($task->load('assignee:id,name,email'), 201);
    }

    public function update(Request $request, Task $task)
    {
        $before = $task->getAttributes();
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'assignee_id' => 'nullable|exists:users,id',
            'estimate_hours' => 'sometimes|numeric|min:0',
            'actual_hours' => 'sometimes|numeric|min:0',
            'complexity' => 'sometimes|integer|min:1|max:5',
            'impact' => 'sometimes|integer|min:1|max:5',
            'due_date' => 'nullable|date',
            'status' => 'sometimes|in:todo,in_progress,done,blocked',
            'blocked_reason' => 'nullable|string',
            'parent_id' => 'nullable|exists:tasks,id',
        ]);

        if (isset($data['complexity']) || isset($data['impact'])) {
            $c = (int) ($data['complexity'] ?? $task->complexity);
            $i = (int) ($data['impact'] ?? $task->impact);
            $data['weight'] = TaskWeightCalculator::compute($c, $i);
        }

        if (array_key_exists('parent_id', $data) && $data['parent_id'] !== null) {
            $this->assertParentInProjectAndDepth((int) $data['parent_id'], $task->project_id);
        }

        $newStatus = $data['status'] ?? $task->status;
        if ($newStatus === 'done' && $task->predecessors()->where('status', '!=', 'done')->exists()) {
            abort(422, 'Finish predecessors before marking this task done (Finish-to-Start).');
        }

        if (($data['status'] ?? $task->status) === 'blocked') {
            $data['blocked_at'] = $task->blocked_at ?? now();
        } elseif (isset($data['status']) && $data['status'] !== 'blocked') {
            $data['blocked_reason'] = null;
            $data['blocked_at'] = null;
        }

        $task->update($data);
        $project = $task->project;
        $this->progressService->syncProjectProgress($project);

        AuditLogger::log('task.updated', $task, $before, $task->fresh()->getAttributes());

        $fresh = $task->fresh()->load('assignee:id,name,email');
        $response = response()->json($fresh);
        $est = (float) $fresh->estimate_hours;
        $act = (float) $fresh->actual_hours;
        if ($est > 0 && $act > $est * 1.5) {
            $response->headers->set('X-PPMS-Warn-Estimate', 'actual_exceeds_1_5x_estimate');
        }

        return $response;
    }

    public function destroy(Task $task)
    {
        $project = $task->project;
        AuditLogger::log('task.deleted', $task, $task->getAttributes(), null);
        $task->delete();
        $this->progressService->syncProjectProgress($project->fresh());

        return response()->noContent();
    }

    /**
     * BR-TK-05: tối đa 2 cấp subtask (parent không được có parent).
     */
    protected function assertParentInProjectAndDepth(int $parentId, int $projectId): void
    {
        $parent = Task::query()->findOrFail($parentId);
        if ((int) $parent->project_id !== $projectId) {
            abort(422, 'Parent task must belong to the same project.');
        }
        if ($parent->parent_id !== null) {
            abort(422, 'Subtasks support at most 2 levels (parent cannot be a subtask).');
        }
    }
}
