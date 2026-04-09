<?php

namespace App\Services\Contracts;

use App\Contracts\Repositories\PaymentRepositoryInterface;
use App\Models\Contract;
use App\Models\PpmsNotification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Orchestrates contract expiration, overdue payments, and in-app reminder notifications (cron / job entry point).
 */
class ContractReminderService
{
    public function __construct(
        protected ContractService $contractService,
        protected PaymentService $payments,
        protected PaymentRepositoryInterface $paymentRepository
    ) {}

    /**
     * @return array{expired_contracts: int, overdue_payments: int, expiration_notifications: int, payment_reminders: int}
     */
    public function run(): array
    {
        $expiredCount = $this->contractService->syncExpiredStatuses();
        $overdueCount = $this->payments->syncOverdueStatuses();

        $expirationWindowDays = (int) config('contracts.expiration_reminder_days', 30);
        $paymentWindowDays = (int) config('contracts.payment_reminder_days', 7);

        $contractsNearingEnd = Contract::query()
            ->active()
            ->whereBetween('end_date', [
                Carbon::now()->toDateString(),
                Carbon::now()->addDays($expirationWindowDays)->toDateString(),
            ])
            ->with(['creator:id,name'])
            ->get();

        foreach ($contractsNearingEnd as $contract) {
            $this->notifyRecipient(
                $contract->created_by,
                'contract.expiration_reminder',
                [
                    'contract_id' => $contract->id,
                    'code' => $contract->code,
                    'end_date' => $contract->end_date?->toDateString(),
                    'message' => 'Contract '.$contract->code.' expires within '.$expirationWindowDays.' days.',
                ]
            );
        }

        $dueSoon = $this->paymentRepository->pendingDueBetween(
            Carbon::now()->startOfDay(),
            Carbon::now()->addDays($paymentWindowDays)->endOfDay()
        );

        foreach ($dueSoon as $payment) {
            $contract = $payment->contract;
            if (! $contract) {
                continue;
            }
            $this->notifyRecipient(
                $contract->created_by,
                'contract.payment_due_reminder',
                [
                    'contract_id' => $contract->id,
                    'payment_id' => $payment->id,
                    'due_date' => $payment->due_date?->toDateString(),
                    'amount' => (string) $payment->amount,
                    'message' => 'Upcoming payment for contract '.$contract->code.'.',
                ]
            );
        }

        $stats = [
            'expired_contracts' => $expiredCount,
            'overdue_payments' => $overdueCount,
            'expiration_notifications' => $contractsNearingEnd->count(),
            'payment_reminders' => $dueSoon->count(),
        ];

        Log::info('contracts.reminders.completed', $stats);

        return $stats;
    }

    private function notifyRecipient(?int $userId, string $type, array $payload): void
    {
        if (! $userId || ! User::query()->whereKey($userId)->exists()) {
            return;
        }

        PpmsNotification::query()->create([
            'type' => $type,
            'recipient_id' => $userId,
            'channel' => 'in_app',
            'payload' => $payload,
            'sent_at' => now(),
        ]);
    }
}
