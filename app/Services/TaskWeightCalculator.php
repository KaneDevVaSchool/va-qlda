<?php

namespace App\Services;

class TaskWeightCalculator
{
    public static function compute(int $complexity, int $impact): float
    {
        $c = max(1, min(5, $complexity));
        $i = max(1, min(5, $impact));

        return round(($c * 0.4) + ($i * 0.6), 6);
    }
}
