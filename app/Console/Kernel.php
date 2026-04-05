<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('ppms:snapshot-kpi')->weeklyOn(0, '23:59')->timezone(config('app.timezone'));
        $schedule->command('ppms:kaizen-reminder')->weeklyOn(4, '9:00')->timezone(config('app.timezone'));
        $schedule->command('ppms:run-alerts')->hourly();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
