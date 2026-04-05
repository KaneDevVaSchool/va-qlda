<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\KpiEngine;
use App\Services\PpmsNotifier;
use Illuminate\Console\Command;

class PpmsRunAlertsCommand extends Command
{
    protected $signature = 'ppms:run-alerts';

    protected $description = 'BR-NT: Blocked task, delayed project, SLA maintenance';

    public function handle(PpmsNotifier $notifier, KpiEngine $kpi): int
    {
        $this->alertBlockedTasks($notifier);
        $this->alertDelayedProjects($notifier);
        $this->alertMaintenanceSla($notifier, $kpi);

        return self::SUCCESS;
    }

    protected function alertBlockedTasks(PpmsNotifier $notifier): void
    {
        $tasks = Task::query()
            ->where('status', 'blocked')
            ->whereNotNull('blocked_at')
            ->where('blocked_at', '<', now()->subDays(2))
            ->with(['assignee', 'project.owner'])
            ->get();

        foreach ($tasks as $task) {
            $ownerId = $task->project?->owner_id;
            $channels = ['in_app', 'email'];
            $body = "Task #{$task->id} {$task->name} blocked > 2 ngày. Lý do: ".($task->blocked_reason ?? 'N/A');

            if ($task->assignee_id) {
                $notifier->notify((int) $task->assignee_id, 'task_blocked', 'Task blocked lâu', $body, $channels, true);
            }
            if ($ownerId) {
                $notifier->notify((int) $ownerId, 'task_blocked', 'Task blocked lâu', $body, $channels, true);
            }
        }
    }

    protected function alertDelayedProjects(PpmsNotifier $notifier): void
    {
        $projects = Project::query()
            ->whereNull('archived_at')
            ->where('status', 'delayed')
            ->with('owner')
            ->get();

        foreach ($projects as $p) {
            if (! $p->owner_id) {
                continue;
            }
            $body = "Dự án «{$p->name}» trạng thái Delayed. Tiến độ: {$p->progress}%.";

            $notifier->notify((int) $p->owner_id, 'project_delayed', 'Dự án trễ', $body, ['in_app', 'email']);

            foreach (User::query()->whereIn('role', ['admin', 'pm'])->pluck('id') as $uid) {
                $notifier->notify((int) $uid, 'project_delayed', 'Dự án trễ', $body, ['in_app']);
            }
        }
    }

    protected function alertMaintenanceSla(PpmsNotifier $notifier, KpiEngine $kpi): void
    {
        foreach (User::query()->pluck('id') as $uid) {
            $rate = $kpi->maintenanceSlaRateForUser((int) $uid);
            if ($rate === null || $rate >= 80) {
                continue;
            }
            $body = "SLA Type 1 của bạn hiện {$rate}% (< 80%).";
            $notifier->notify((int) $uid, 'sla_critical', 'SLA dưới ngưỡng', $body, ['in_app', 'email'], true);
        }
    }
}
