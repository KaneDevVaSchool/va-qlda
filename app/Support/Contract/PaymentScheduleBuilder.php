<?php

namespace App\Support\Contract;

use App\Enums\PaymentCycle;
use Carbon\Carbon;
use InvalidArgumentException;

/**
 * Builds equal-split payment rows between start and end according to the billing cycle.
 */
class PaymentScheduleBuilder
{
    /**
     * @return list<array{due_date: string, amount: string}>
     */
    public static function build(Carbon $start, Carbon $end, string $totalValue, PaymentCycle $cycle): array
    {
        if ($end->lt($start)) {
            throw new InvalidArgumentException('Contract end_date must be on or after start_date.');
        }

        $dates = self::dueDates($start, $end, $cycle);
        $count = count($dates);
        if ($count === 0) {
            return [];
        }

        $totalCents = (int) round((float) $totalValue * 100);
        $base = intdiv($totalCents, $count);
        $remainder = $totalCents - ($base * $count);

        $rows = [];
        foreach ($dates as $i => $d) {
            $cents = $base;
            if ($i === $count - 1) {
                $cents += $remainder;
            }
            $rows[] = [
                'due_date' => $d->toDateString(),
                'amount' => number_format($cents / 100, 2, '.', ''),
            ];
        }

        return $rows;
    }

    /**
     * @return list<Carbon>
     */
    private static function dueDates(Carbon $start, Carbon $end, PaymentCycle $cycle): array
    {
        $dates = [];
        $cursor = $start->copy()->startOfDay();
        $endDay = $end->copy()->startOfDay();

        while ($cursor->lte($endDay)) {
            $dates[] = $cursor->copy();
            $cursor = match ($cycle) {
                PaymentCycle::Monthly => $cursor->copy()->addMonth(),
                PaymentCycle::Quarterly => $cursor->copy()->addMonths(3),
                PaymentCycle::Yearly => $cursor->copy()->addYear(),
            };
        }

        return $dates;
    }
}
