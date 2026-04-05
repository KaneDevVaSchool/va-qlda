<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TaskDependency;
use App\Services\AuditLogger;
use App\Services\TaskDependencyService;
use Illuminate\Http\Request;

class TaskDependencyController extends Controller
{
    public function __construct(
        protected TaskDependencyService $dependencyService
    ) {}

    public function index(Task $task)
    {
        return [
            'predecessors' => $task->predecessors()->get(['tasks.id', 'tasks.name', 'tasks.status']),
            'successors' => $task->successors()->get(['tasks.id', 'tasks.name', 'tasks.status']),
        ];
    }

    public function store(Request $request, Task $task)
    {
        $data = $request->validate([
            'predecessor_task_id' => 'required|exists:tasks,id',
        ]);

        $predId = (int) $data['predecessor_task_id'];
        $pred = Task::query()->findOrFail($predId);

        if ($pred->project_id !== $task->project_id) {
            abort(422, 'Predecessor must belong to same project.');
        }

        if ($this->dependencyService->wouldCreateCycle($task->id, $predId)) {
            abort(422, 'Dependency would create a cycle.');
        }

        $row = TaskDependency::query()->firstOrCreate([
            'successor_task_id' => $task->id,
            'predecessor_task_id' => $predId,
        ]);

        AuditLogger::log('task_dependency.created', $row, null, $row->toArray());

        return response()->json($row, 201);
    }

    public function destroy(TaskDependency $dependency)
    {
        AuditLogger::log('task_dependency.deleted', $dependency, $dependency->getAttributes(), null);
        $dependency->delete();

        return response()->noContent();
    }
}
