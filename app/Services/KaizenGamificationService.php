<?php

namespace App\Services;

use App\Models\Kaizen;
use App\Models\KaizenWeeklyReminder;
use App\Models\User;
use Carbon\Carbon;

class KaizenGamificationService
{
    /**
     * @return list<array{badge: string, label: string, level: string}>
     */
    public function badgesFor(User $user): array
    {
        $badges = [];
        $verified = Kaizen::query()
            ->where('submitter_id', $user->id)
            ->where('status', 'verified')
            ->count();

        if ($verified >= 3) {
            $badges[] = ['badge' => 'kaizen_bronze', 'label' => 'Kaizen Bronze', 'level' => '3+ verified'];
        }
        if ($verified >= 8) {
            $badges[] = ['badge' => 'kaizen_silver', 'label' => 'Kaizen Silver', 'level' => '8+ verified'];
        }
        if ($verified >= 20) {
            $badges[] = ['badge' => 'kaizen_gold', 'label' => 'Kaizen Gold', 'level' => '20+ verified'];
        }

        $streak = $this->consecutiveVerifiedWeeks($user->id);
        if ($streak >= 4) {
            $badges[] = ['badge' => 'streak_4', 'label' => 'Streak 4 tuần', 'level' => (string) $streak.' tuần liên tiếp'];
        }

        return $badges;
    }

    protected function consecutiveVerifiedWeeks(int $userId): int
    {
        $weeks = Kaizen::query()
            ->where('submitter_id', $userId)
            ->where('status', 'verified')
            ->orderByDesc('week_start')
            ->pluck('week_start')
            ->map(fn ($d) => Carbon::parse($d)->startOfWeek()->toDateString())
            ->unique()
            ->values();

        if ($weeks->isEmpty()) {
            return 0;
        }

        $expected = Carbon::parse($weeks[0])->startOfWeek();
        $streak = 0;
        foreach ($weeks as $w) {
            $d = Carbon::parse($w)->startOfWeek();
            if ($d->toDateString() === $expected->toDateString()) {
                $streak++;
                $expected->subWeek();
            } else {
                break;
            }
        }

        return $streak;
    }

    /**
     * @return array{reminders_sent: int, fulfilled: int, skip_rate_pct: float}
     */
    public function reminderCompliance(): array
    {
        $sent = KaizenWeeklyReminder::query()->count();
        $fulfilled = KaizenWeeklyReminder::query()->whereNotNull('fulfilled_at')->count();
        $skipRate = $sent > 0 ? round((1 - ($fulfilled / $sent)) * 100, 2) : 0.0;

        return [
            'reminders_sent' => $sent,
            'fulfilled' => $fulfilled,
            'skip_rate_pct' => $skipRate,
        ];
    }
}
