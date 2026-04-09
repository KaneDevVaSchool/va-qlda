<?php

namespace App\Repositories;

use App\Contracts\Repositories\PaymentRepositoryInterface;
use App\Models\ContractPayment;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PaymentRepository implements PaymentRepositoryInterface
{
    public function upcomingWithinDays(int $days): Collection
    {
        $from = Carbon::now()->toDateString();
        $to = Carbon::now()->addDays($days)->toDateString();

        return ContractPayment::query()
            ->pending()
            ->forActiveContracts()
            ->whereBetween('due_date', [$from, $to])
            ->with(['contract.vendor'])
            ->orderBy('due_date')
            ->get();
    }

    public function pendingDueBetween(Carbon $from, Carbon $to): Collection
    {
        return ContractPayment::query()
            ->pending()
            ->forActiveContracts()
            ->whereBetween('due_date', [$from->toDateString(), $to->toDateString()])
            ->with(['contract'])
            ->orderBy('due_date')
            ->get();
    }
}
