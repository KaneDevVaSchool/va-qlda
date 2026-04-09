<?php

namespace App\Contracts\Repositories;

use App\Models\ContractPayment;
use Illuminate\Support\Collection;

interface PaymentRepositoryInterface
{
    /**
     * @return Collection<int, ContractPayment>
     */
    public function upcomingWithinDays(int $days): Collection;

    /**
     * @return Collection<int, ContractPayment>
     */
    public function pendingDueBetween(\Carbon\Carbon $from, \Carbon\Carbon $to): Collection;
}
