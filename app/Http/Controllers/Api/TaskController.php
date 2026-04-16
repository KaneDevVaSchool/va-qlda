<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskParticipant;
use App\Services\AuditLogger;
use App\Services\ProjectProgressService;
use App\Services\TaskProgressDisplayService;
use App\Services\TaskWeightCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    public function __construct(
        protected ProjectProgressService $progressService
    ) {}

    public function index(Project $project)
    {
        $tasks = $project->tasks()
            ->with([
                'assignee:id,name,email',
                'taskParticipants',
                'projectPhase:id,name',
                'children',
                'predecessors:id,name,status',
            ])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $tasks->each(function (Task $t) {
            $t->mergeParticipantArraysIntoAttributes();
            $t->makeHidden(['task_participants']);
        });

        return $tasks;
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

        $task->load(['assignee:id,name,email', 'projectPhase:id,name', 'taskParticipants']);
        $task->mergeParticipantArraysIntoAttributes();
        $task->makeHidden(['task_participants']);

        return response()->json($task, 201);
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
            'tasks.*.assignee_id' => 'nullable|exists:cms.users,id',
            'tasks.*.assignee_ids' => 'nullable|array|max:40',
            'tasks.*.assignee_ids.*' => 'integer|exists:cms.users,id',
            'tasks.*.owner_ids' => 'nullable|array|max:40',
            'tasks.*.owner_ids.*' => 'integer|exists:cms.users,id',
            'tasks.*.follower_ids' => 'nullable|array|max:80',
            'tasks.*.follower_ids.*' => 'integer|exists:cms.users,id',
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
                $task->load(['assignee:id,name,email', 'projectPhase:id,name', 'taskParticipants']);
                $task->mergeParticipantArraysIntoAttributes();
                $task->makeHidden(['task_participants']);
                $created[] = $task;
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
        $assigneeIds = static::normalizeParticipantIds($data['assignee_ids'] ?? null);
        $ownerIds = static::normalizeParticipantIds($data['owner_ids'] ?? null);
        $followerIds = static::normalizeParticipantIds($data['follower_ids'] ?? null);

        $primaryAssignee = $assigneeIds[0] ?? ($data['assignee_id'] ?? null);

        $weight = TaskWeightCalculator::compute((int) $data['complexity'], (int) $data['impact']);

        $fill = Arr::only($data, (new Task)->getFillable());
        unset($fill['assignee_id']);

        $task = $project->tasks()->create(array_merge($fill, [
            'weight' => $weight,
            'status' => $data['status'] ?? 'todo',
            'actual_hours' => $data['actual_hours'] ?? 0,
            'progress_mode' => $data['progress_mode'] ?? 'status_default',
            'assignee_id' => $primaryAssignee,
        ]));

        $toSyncAssignees = $assigneeIds;
        if ($toSyncAssignees === [] && $primaryAssignee) {
            $toSyncAssignees = [(int) $primaryAssignee];
        }

        $task->syncTaskParticipants($toSyncAssignees, $ownerIds, $followerIds);

        return $task->fresh();
    }

    /**
     * @param  mixed  $value
     * @return list<int>
     */
    protected static function normalizeParticipantIds($value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $out = [];
        $seen = [];
        foreach ($value as $id) {
            $n = (int) $id;
            if ($n <= 0 || isset($seen[$n])) {
                continue;
            }
            $seen[$n] = true;
            $out[] = $n;
        }

        return $out;
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
            'assignee_id' => 'nullable|exists:cms.users,id',
            'assignee_ids' => 'nullable|array|max:40',
            'assignee_ids.*' => 'integer|exists:cms.users,id',
            'owner_ids' => 'nullable|array|max:40',
            'owner_ids.*' => 'integer|exists:cms.users,id',
            'follower_ids' => 'nullable|array|max:80',
            'follower_ids.*' => 'integer|exists:cms.users,id',
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
            'assignee_id' => 'nullable|exists:cms.users,id',
            'assignee_ids' => 'nullable|array|max:40',
            'assignee_ids.*' => 'integer|exists:cms.users,id',
            'owner_ids' => 'nullable|array|max:40',
            'owner_ids.*' => 'integer|exists:cms.users,id',
            'follower_ids' => 'nullable|array|max:80',
            'follower_ids.*' => 'integer|exists:cms.users,id',
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

        $participantSync = $request->has('assignee_ids') || $request->has('owner_ids') || $request->has('follower_ids');

        if ($participantSync) {
            unset($data['assignee_id']);
        }

        $legacyAssigneeOnly = ! $participantSync && array_key_exists('assignee_id', $data);
        if ($legacyAssigneeOnly) {
            unset($data['assignee_id']);
        }

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

        if ($participantSync) {
            $task->loadMissing('taskParticipants');
            $g = $task->taskParticipants->groupBy('role');
            $aids = $request->has('assignee_ids')
                ? static::normalizeParticipantIds($request->input('assignee_ids'))
                : $g->get(TaskParticipant::ROLE_ASSIGNEE, collect())->pluck('user_id')->all();
            $oids = $request->has('owner_ids')
                ? static::normalizeParticipantIds($request->input('owner_ids'))
                : $g->get(TaskParticipant::ROLE_OWNER, collect())->pluck('user_id')->all();
            $fids = $request->has('follower_ids')
                ? static::normalizeParticipantIds($request->input('follower_ids'))
                : $g->get(TaskParticipant::ROLE_FOLLOWER, collect())->pluck('user_id')->all();
            $task->syncTaskParticipants($aids, $oids, $fids);
        } elseif ($legacyAssigneeOnly) {
            $task->loadMissing('taskParticipants');
            $g = $task->taskParticipants->groupBy('role');
            $owners = $g->get(TaskParticipant::ROLE_OWNER, collect())->pluck('user_id')->all();
            $followers = $g->get(TaskParticipant::ROLE_FOLLOWER, collect())->pluck('user_id')->all();
            $raw = $request->input('assignee_id');
            $aid = $raw ? [(int) $raw] : [];
            $task->syncTaskParticipants($aid, $owners, $followers);
        }

        $project = $task->project;
        $this->progressService->syncProjectProgress($project);

        AuditLogger::log('task.updated', $task, $before, $task->fresh()->getAttributes());

        $fresh = $task->fresh()->load(['assignee:id,name,email', 'projectPhase:id,name', 'taskParticipants']);
        $fresh->mergeParticipantArraysIntoAttributes();
        $fresh->makeHidden(['task_participants']);

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
