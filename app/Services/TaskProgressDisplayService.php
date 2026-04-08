<?php

namespace App\Services;

use App\Models\Task;
use Carbon\Carbon;

/**
 * Display / Gantt progress % theo "cách tính tiến độ" (progress_mode).
 */
class TaskProgressDisplayService
{
    public const MODES = [
        'status_default',
        'manual_pct',
        'volume_ratio',
        'checklist_ratio',
        'child_weight',
        'time_auto',
    ];

    public static function percent(Task $task): float
    {
        $task->loadMissing('children');

        return round(self::compute($task), 2);
    }

    protected static function compute(Task $task): float
    {
        $mode = $task->progress_mode ?: 'status_default';

        return match ($mode) {
            'manual_pct' => self::clampPct((float) ($task->manual_progress_pct ?? 0)),
            'volume_ratio' => self::volumeRatio($task),
            'checklist_ratio' => self::checklistRatio($task),
            'child_weight' => self::childWeight($task),
            'time_auto' => self::timeAuto($task),
            default => self::statusFallback($task),
        };
    }

    protected static function statusFallback(Task $task): float
    {
        return match ($task->status) {
            'done' => 100.0,
            'in_progress' => 50.0,
            default => 0.0,
        };
    }

    protected static function clampPct(float $v): float
    {
        return max(0.0, min(100.0, $v));
    }

    protected static function volumeRatio(Task $task): float
    {
        $total = (int) ($task->volume_total ?? 0);
        if ($total <= 0) {
            return self::statusFallback($task);
        }

        $done = min($total, (int) ($task->volume_done ?? 0));

        return self::clampPct(($done / $total) * 100);
    }

    protected static function checklistRatio(Task $task): float
    {
        $total = (int) ($task->checklist_total ?? 0);
        if ($total <= 0) {
            return self::statusFallback($task);
        }

        $done = min($total, (int) ($task->checklist_done ?? 0));

        return self::clampPct(($done / $total) * 100);
    }

    protected static function childWeight(Task $task): float
    {
        $children = $task->relationLoaded('children')
            ? $task->children
            : $task->children()->get();

        if ($children->isEmpty()) {
            return self::statusFallback($task);
        }

        $sumW = 0.0;
        $sumWeighted = 0.0;
        foreach ($children as $c) {
            $c->loadMissing('children');
            $w = max(0.000001, (float) $c->weight);
            $p = self::compute($c) / 100;
            $sumW += $w;
            $sumWeighted += $w * $p;
        }

        return $sumW > 0 ? self::clampPct(($sumWeighted / $sumW) * 100) : 0.0;
    }

    protected static function timeAuto(Task $task): float
    {
        if (! $task->due_date) {
            return self::statusFallback($task);
        }

        $start = $task->created_at
            ? Carbon::parse($task->created_at)->startOfDay()
            : now()->startOfDay();
        $end = Carbon::parse($task->due_date)->startOfDay();
        $now = now()->startOfDay();

        $totalDays = max(1, $start->diffInDays($end));
        if ($now->lte($end)) {
            $elapsed = max(0, min($totalDays, $start->diffInDays($now)));

            return self::clampPct(($elapsed / $totalDays) * 100);
        }

        $lateDays = $end->diffInDays($now);
        $penalty = min(50.0, $lateDays * 10.0);

        return self::clampPct(100.0 - $penalty);
    }
}
