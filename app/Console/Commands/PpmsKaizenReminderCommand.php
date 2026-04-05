<?php

namespace App\Console\Commands;

use App\Models\KaizenWeeklyReminder;
use App\Models\User;
use App\Services\PpmsNotifier;
use Carbon\Carbon;
use Illuminate\Console\Command;

class PpmsKaizenReminderCommand extends Command
{
    protected $signature = 'ppms:kaizen-reminder';

    protected $description = 'BR-KZ-01 / BR-NT-04: Nhắc Kaizen hàng tuần (Thứ 5 09:00)';

    public function handle(PpmsNotifier $notifier): int
    {
        $body = 'Vui lòng gửi ít nhất 1 Kaizen trong tuần (đo lường được).';
        $weekStart = Carbon::now()->startOfWeek()->toDateString();

        User::query()->chunk(50, function ($users) use ($notifier, $body, $weekStart) {
            foreach ($users as $user) {
                $rem = KaizenWeeklyReminder::firstOrCreate(
                    [
                        'user_id' => $user->id,
                        'week_start' => $weekStart,
                    ],
                    [
                        'reminded_at' => null,
                        'fulfilled_at' => null,
                    ]
                );

                if ($rem->fulfilled_at !== null || $rem->reminded_at !== null) {
                    continue;
                }

                $notifier->notify(
                    (int) $user->id,
                    'kaizen_reminder',
                    'Nhắc Kaizen tuần này',
                    $body,
                    ['in_app', 'email']
                );
                $rem->forceFill(['reminded_at' => now()])->save();
            }
        });

        $this->info('Kaizen reminders sent.');

        return self::SUCCESS;
    }
}
