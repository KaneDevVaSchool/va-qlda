<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Services\AuditLogger;
use App\Services\ProjectProgressService;
use Illuminate\Http\Request;

class TaskBulkController extends Controller
{
    public function __construct(
        protected ProjectProgressService $progressService
    ) {}

    public function update(Request $request)
    {
        $data = $request->validate([
            'task_ids' => 'required|array|min:1|max:50',
            'task_ids.*' => 'integer|exists:tasks,id',
            'assignee_id' => 'nullable|exists:cms.users,id',
            'status' => 'nullable|in:todo,in_progress,done,blocked',
        ]);

        $tasks = Task::query()->whereIn('id', $data['task_ids'])->get();
        $projectIds = [];

        foreach ($tasks as $task) {
            $before = $task->getAttributes();
            $updates = [];
            if (array_key_exists('assignee_id', $data)) {
                $updates['assignee_id'] = $data['assignee_id'];
            }
            if (! empty($data['status'])) {
                $updates['status'] = $data['status'];
                if ($data['status'] === 'blocked') {
                    $updates['blocked_at'] = $task->blocked_at ?? now();
                } else {
                    $updates['blocked_reason'] = null;
                    $updates['blocked_at'] = null;
                }
            }
            if ($updates !== []) {
                $task->update($updates);
                AuditLogger::log('task.bulk_updated', $task, $before, $task->fresh()->getAttributes());
            }
            $projectIds[$task->project_id] = true;
        }

        foreach (array_keys($projectIds) as $pid) {
            $p = \App\Models\Project::query()->find($pid);
            if ($p) {
                $this->progressService->syncProjectProgress($p);
            }
        }

        return response()->json(['updated' => $tasks->count()]);
    }
}
