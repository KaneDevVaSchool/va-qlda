<?php

namespace App\Console\Commands;

use App\Jobs\ProcessContractRemindersJob;
use Illuminate\Console\Command;

class ContractsRunRemindersCommand extends Command
{
    protected $signature = 'contracts:run-reminders';

    protected $description = 'Expire active contracts past end date, mark overdue payments, and queue in-app reminders';

    public function handle(): int
    {
        ProcessContractRemindersJob::dispatch();
        $this->info('Contract reminder job queued.');

        return self::SUCCESS;
    }
}
