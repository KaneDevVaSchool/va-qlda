<?php

namespace App\Jobs;

use App\Services\Contracts\ContractReminderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Scheduled job entry point: delegates to {@see ContractReminderService}.
 */
class ProcessContractRemindersJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function handle(ContractReminderService $reminders): void
    {
        $reminders->run();
    }
}
