<?php

namespace App\Services;

class EvaluationScoring
{
    public static function total(float $p1, float $p2, float $p3): float
    {
        return round(($p1 * 0.4) + ($p2 * 0.3) + ($p3 * 0.3), 2);
    }

    public static function grade(float $total): string
    {
        if ($total >= 90) {
            return 'A';
        }
        if ($total >= 75) {
            return 'B';
        }
        if ($total >= 60) {
            return 'C';
        }

        return 'D';
    }
}
