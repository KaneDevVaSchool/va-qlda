<?php

namespace App\Services;

use App\Models\TaskDependency;

class TaskDependencyService
{
    /** @return list<int> */
    public function transitivePredecessorIds(int $taskId): array
    {
        $seen = [];
        $stack = TaskDependency::query()
            ->where('successor_task_id', $taskId)
            ->pluck('predecessor_task_id')
            ->all();

        while ($stack !== []) {
            $id = (int) array_pop($stack);
            if (isset($seen[$id])) {
                continue;
            }
            $seen[$id] = true;
            $next = TaskDependency::query()
                ->where('successor_task_id', $id)
                ->pluck('predecessor_task_id')
                ->all();
            foreach ($next as $n) {
                $stack[] = (int) $n;
            }
        }

        return array_keys($seen);
    }

    public function wouldCreateCycle(int $successorTaskId, int $predecessorTaskId): bool
    {
        if ($successorTaskId === $predecessorTaskId) {
            return true;
        }

        return in_array($successorTaskId, $this->transitivePredecessorIds($predecessorTaskId), true);
    }
}
