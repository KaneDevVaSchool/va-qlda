<?php

namespace App\Services\Contracts;

use App\Contracts\Repositories\PaymentRepositoryInterface;
use App\Enums\ContractPaymentStatus;
use App\Enums\ContractStatus;
use App\Models\Contract;
use App\Models\ContractPayment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(
        protected ContractAuditService $audit,
        protected PaymentRepositoryInterface $paymentRepository
    ) {}

    /**
     * Replace all payment rows for a contract (draft edits / regeneration).
     * Uses bulk insert to avoid N+1 creates.
     *
     * @param  list<array{due_date: string, amount: string}>  $rows
     */
    public function replaceSchedule(Contract $contract, array $rows): void
    {
        DB::transaction(function () use ($contract, $rows) {
            $contract->payments()->delete();

            if ($rows === []) {
                return;
            }

            $now = now();
            $batch = [];
            foreach ($rows as $row) {
                $batch[] = [
                    'contract_id' => $contract->id,
                    'due_date' => $row['due_date'],
                    'amount' => $row['amount'],
                    'status' => ContractPaymentStatus::Pending->value,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            foreach (array_chunk($batch, 500) as $chunk) {
                ContractPayment::query()->insert($chunk);
            }
        });
    }

    /**
     * Mark a payment paid; validates it belongs to the given contract and the contract is active.
     */
    public function markPaidForContract(Contract $contract, ContractPayment $payment, User $user): ContractPayment
    {
        if ($payment->contract_id !== $contract->id) {
            abort(404);
        }

        if ($contract->status !== ContractStatus::Active) {
            abort(422, 'Payments can only be marked paid for active contracts.');
        }

        if ($payment->status === ContractPaymentStatus::Paid) {
            return $payment;
        }

        $previous = $payment->only(['status', 'paid_at']);
        $payment->update([
            'status' => ContractPaymentStatus::Paid,
            'paid_at' => now(),
        ]);

        $this->audit->log(
            $contract,
            'payment.mark_paid',
            $previous,
            $payment->only(['status', 'paid_at']),
            $user->id
        );

        return $payment->fresh();
    }

    public function orderedInstallments(Contract $contract): Collection
    {
        return $contract->payments()->orderBy('due_date')->get();
    }

    public function upcomingInstallmentsWithinDays(int $days): Collection
    {
        return $this->paymentRepository->upcomingWithinDays(
            $this->clampUpcomingWindowDays($days)
        );
    }

    /**
     * Pending payments past due_date become overdue (active contracts only).
     */
    public function syncOverdueStatuses(): int
    {
        $today = Carbon::today()->toDateString();

        return ContractPayment::query()
            ->pending()
            ->forActiveContracts()
            ->whereDate('due_date', '<', $today)
            ->update(['status' => ContractPaymentStatus::Overdue->value]);
    }

    private function clampUpcomingWindowDays(int $days): int
    {
        return min(365, max(1, $days));
    }
}
