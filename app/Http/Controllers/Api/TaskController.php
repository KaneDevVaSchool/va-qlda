<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use App\Services\AuditLogger;
use App\Services\ProjectProgressService;
use App\Services\TaskProgressDisplayService;
use App\Services\TaskWeightCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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
                'projectPhase:id,name',
                'children',
                'predecessors:id,name,status',
            ])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    public function store(Request $request, Project $project)
    {
        $data = $request->validate($this->taskStoreRules($project));

        if (! empty($data['parent_id'])) {
            $this->assertParentInProjectAndDepth((int) $data['parent_id'], $project->id);
        }

        $task = $this->createTaskRecord($project, $data);

        $this->progressService->syncProjectProgress($project->fresh());
        AuditLogger::log('task.created', $task, null, $task->toArray());

        return response()->json($task->load(['assignee:id,name,email', 'projectPhase:id,name']), 201);
    }

    /**
     * Tạo nhiều công việc trong một request (thêm hàng loạt).
     */
    public function bulkStore(Request $request, Project $project)
    {
        $data = $request->validate([
            'tasks' => 'required|array|min:1|max:40',
            'tasks.*.name' => 'required|string|max:255',
            'tasks.*.description' => 'nullable|string',
            'tasks.*.parent_id' => 'nullable|exists:tasks,id',
            'tasks.*.assignee_id' => 'nullable|exists:users,id',
            'tasks.*.estimate_hours' => 'required|numeric|min:0',
            'tasks.*.actual_hours' => 'sometimes|numeric|min:0',
            'tasks.*.complexity' => 'required|integer|min:1|max:5',
            'tasks.*.impact' => 'required|integer|min:1|max:5',
            'tasks.*.due_date' => 'nullable|date',
            'tasks.*.status' => 'sometimes|in:todo,in_progress,done,blocked',
            'tasks.*.project_phase_id' => [
                'nullable',
                Rule::exists('project_phases', 'id')->where('project_id', $project->id),
            ],
            'tasks.*.progress_mode' => 'nullable|in:'.implode(',', TaskProgressDisplayService::MODES),
            'tasks.*.manual_progress_pct' => 'nullable|numeric|min:0|max:100',
            'tasks.*.volume_total' => 'nullable|integer|min:0',
            'tasks.*.volume_done' => 'nullable|integer|min:0',
            'tasks.*.checklist_total' => 'nullable|integer|min:0',
            'tasks.*.checklist_done' => 'nullable|integer|min:0',
            'tasks.*.category' => 'nullable|string|max:128',
        ]);

        $created = [];

        DB::transaction(function () use ($project, $data, &$created) {
            foreach ($data['tasks'] as $row) {
                if (! empty($row['parent_id'])) {
                    $this->assertParentInProjectAndDepth((int) $row['parent_id'], $project->id);
                }
                $task = $this->createTaskRecord($project, $row);
                AuditLogger::log('task.created', $task, null, $task->toArray());
                $created[] = $task->load(['assignee:id,name,email', 'projectPhase:id,name']);
            }
        });

        $this->progressService->syncProjectProgress($project->fresh());

        return response()->json(['tasks' => $created], 201);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function createTaskRecord(Project $project, array $data): Task
    {
        $weight = TaskWeightCalculator::compute((int) $data['complexity'], (int) $data['impact']);

        return $project->tasks()->create(array_merge($data, [
            'weight' => $weight,
            'status' => $data['status'] ?? 'todo',
            'actual_hours' => $data['actual_hours'] ?? 0,
            'progress_mode' => $data['progress_mode'] ?? 'status_default',
        ]));
    }

    /**
     * @return array<string, mixed>
     */
    protected function taskStoreRules(Project $project): array
    {
        return [
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
            'project_phase_id' => [
                'nullable',
                Rule::exists('project_phases', 'id')->where('project_id', $project->id),
            ],
            'progress_mode' => 'sometimes|in:'.implode(',', TaskProgressDisplayService::MODES),
            'manual_progress_pct' => 'nullable|numeric|min:0|max:100',
            'volume_total' => 'nullable|integer|min:0',
            'volume_done' => 'nullable|integer|min:0',
            'checklist_total' => 'nullable|integer|min:0',
            'checklist_done' => 'nullable|integer|min:0',
            'category' => 'nullable|string|max:128',
        ];
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
            'project_phase_id' => [
                'nullable',
                Rule::exists('project_phases', 'id')->where('project_id', $task->project_id),
            ],
            'progress_mode' => 'sometimes|in:'.implode(',', TaskProgressDisplayService::MODES),
            'manual_progress_pct' => 'nullable|numeric|min:0|max:100',
            'volume_total' => 'nullable|integer|min:0',
            'volume_done' => 'nullable|integer|min:0',
            'checklist_total' => 'nullable|integer|min:0',
            'checklist_done' => 'nullable|integer|min:0',
            'category' => 'nullable|string|max:128',
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

        $fresh = $task->fresh()->load(['assignee:id,name,email', 'projectPhase:id,name']);
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
