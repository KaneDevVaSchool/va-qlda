<?php

namespace App\Console\Commands;

use App\Services\KpiWeeklySnapshotService;
use App\Services\PpmsNotifier;
use Carbon\Carbon;
use Illuminate\Console\Command;

class PpmsSnapshotKpiCommand extends Command
{
    protected $signature = 'ppms:snapshot-kpi {--date= : ISO date for week ending context}';

    protected $description = 'BR-KPI-06: Weekly KPI snapshots (Sunday job)';

    public function handle(KpiWeeklySnapshotService $snapshots, PpmsNotifier $notifier): int
    {
        $at = $this->option('date') ? Carbon::parse($this->option('date')) : Carbon::now();
        $snapshots->run($at);
        $this->info('KPI snapshots completed for week ending '.$snapshots->weekEndingDate($at)->toDateString());

        foreach (\App\Models\User::query()->whereIn('role', ['admin', 'pm'])->pluck('id') as $uid) {
            $notifier->notify((int) $uid, 'kpi_snapshot_done', 'KPI snapshot', 'Weekly KPI snapshot đã hoàn tất.', ['in_app']);
        }

        return self::SUCCESS;
    }
}
