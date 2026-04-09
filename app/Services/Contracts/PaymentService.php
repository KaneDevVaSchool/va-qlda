<?php

namespace App\Services\Contracts;

use App\Contracts\Repositories\PaymentRepositoryInterface;
use App\Enums\ContractPaymentStatus;
use App\Enums\ContractStatus;
use App\Models\Contract;
use App\Models\ContractPayment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(
        protected ContractAuditService $audit,
        protected PaymentRepositoryInterface $paymentRepository,
        protected FileService $files
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
                    'amount_paid' => '0',
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
     * Record a payment toward an installment (full or partial). Optional proof file.
     */
    public function markPaidForContract(
        Contract $contract,
        ContractPayment $payment,
        User $user,
        string $paidAmountInput,
        ?UploadedFile $proofFile = null
    ): ContractPayment {
        if ($payment->contract_id !== $contract->id) {
            abort(404);
        }

        if ($contract->status !== ContractStatus::Active) {
            abort(422, 'Payments can only be marked paid for active contracts.');
        }

        $total = (float) $payment->amount;
        $paidSoFar = (float) $payment->amount_paid;
        if ($payment->status === ContractPaymentStatus::Paid) {
            return $payment;
        }
        if ($paidSoFar >= $total - 0.0001) {
            return $payment->fresh();
        }

        $add = round((float) $paidAmountInput, 2);
        $remaining = round(max(0, $total - $paidSoFar), 2);
        if ($add <= 0) {
            abort(422, 'Paid amount must be greater than zero.');
        }
        if ($add > $remaining + 0.0001) {
            abort(422, 'Paid amount cannot exceed the remaining balance for this installment.');
        }

        return DB::transaction(function () use ($contract, $payment, $user, $total, $paidSoFar, $add, $proofFile) {
            $proofId = null;
            if ($proofFile !== null) {
                $stored = $this->files->store($contract, $proofFile, $user, false, null);
                $proofId = $stored['file']->id;
            }

            $newPaid = round($paidSoFar + $add, 2);
            $isFull = $newPaid >= $total - 0.0001;
            $status = $isFull ? ContractPaymentStatus::Paid : ContractPaymentStatus::Partial;

            $previous = $payment->only(['status', 'paid_at', 'amount_paid', 'proof_file_id']);

            $updates = [
                'amount_paid' => (string) $newPaid,
                'paid_at' => now(),
                'status' => $status,
            ];
            if ($proofId !== null) {
                $updates['proof_file_id'] = $proofId;
            }

            $payment->update($updates);

            $this->audit->log(
                $contract,
                'payment.mark_paid',
                $previous,
                $payment->fresh()->only(['status', 'paid_at', 'amount_paid', 'proof_file_id']),
                $user->id
            );

            return $payment->fresh(['proofFile']);
        });
    }

    public function orderedInstallments(Contract $contract): Collection
    {
        return $contract->payments()->orderBy('due_date')->with('proofFile')->get();
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
