<?php

namespace App\Services\Contracts;

use App\Enums\ContractStatus;
use App\Enums\PaymentCycle;
use App\Models\Contract;
use App\Models\User;
use App\Support\Contract\PaymentScheduleBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ContractService
{
    public function __construct(
        protected PaymentService $payments,
        protected ContractAuditService $audit
    ) {}

    public function create(array $data, User $creator): Contract
    {
        return DB::transaction(function () use ($data, $creator) {
            $contract = Contract::query()->create([
                'code' => $this->generateUniqueCode(),
                'vendor_id' => $data['vendor_id'],
                'product_id' => $data['product_id'],
                'department_id' => $data['department_id'],
                'scope' => $data['scope'] ?? null,
                'status' => ContractStatus::Draft,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'total_value' => $data['total_value'],
                'payment_cycle' => PaymentCycle::from($data['payment_cycle']),
                'created_by' => $creator->id,
                'approved_by' => null,
            ]);

            $this->rebuildInstallmentSchedule($contract);

            $this->audit->log(
                $contract,
                'contract.created',
                null,
                $contract->fresh()->toArray(),
                $creator->id
            );

            return $contract->fresh(['vendor', 'product', 'department', 'payments']);
        });
    }

    public function update(Contract $contract, array $data, User $user): Contract
    {
        if ($contract->status !== ContractStatus::Draft) {
            abort(422, 'Only draft contracts can be edited.');
        }

        return DB::transaction(function () use ($contract, $data, $user) {
            $snapshotBefore = $contract->toArray();

            $contract->fill([
                'vendor_id' => $data['vendor_id'] ?? $contract->vendor_id,
                'product_id' => $data['product_id'] ?? $contract->product_id,
                'department_id' => $data['department_id'] ?? $contract->department_id,
                'scope' => array_key_exists('scope', $data) ? $data['scope'] : $contract->scope,
                'start_date' => $data['start_date'] ?? $contract->start_date,
                'end_date' => $data['end_date'] ?? $contract->end_date,
                'total_value' => $data['total_value'] ?? $contract->total_value,
                'payment_cycle' => isset($data['payment_cycle'])
                    ? PaymentCycle::from($data['payment_cycle'])
                    : $contract->payment_cycle,
            ]);
            $contract->save();

            $this->rebuildInstallmentSchedule($contract->fresh());

            $this->audit->log(
                $contract,
                'contract.updated',
                $snapshotBefore,
                $contract->fresh()->toArray(),
                $user->id
            );

            return $contract->fresh(['vendor', 'product', 'department', 'payments']);
        });
    }

    public function delete(Contract $contract, User $user): void
    {
        if ($contract->status !== ContractStatus::Draft) {
            abort(422, 'Only draft contracts can be deleted.');
        }

        DB::transaction(function () use ($contract, $user) {
            $snapshot = $contract->toArray();
            $this->audit->log($contract, 'contract.deleted', $snapshot, null, $user->id);
            $contract->delete();
        });
    }

    public function terminate(Contract $contract, User $user): Contract
    {
        if ($contract->status !== ContractStatus::Active) {
            abort(422, 'Only active contracts can be terminated.');
        }

        $previous = $contract->only(['status']);
        $contract->update(['status' => ContractStatus::Terminated]);

        $this->audit->log(
            $contract,
            'contract.terminated',
            $previous,
            $contract->only(['status']),
            $user->id
        );

        return $contract->fresh();
    }

    /**
     * Mark active contracts past end_date as expired.
     */
    public function syncExpiredStatuses(): int
    {
        return Contract::query()
            ->where('status', ContractStatus::Active->value)
            ->whereDate('end_date', '<', now()->toDateString())
            ->update(['status' => ContractStatus::Expired->value]);
    }

    private function rebuildInstallmentSchedule(Contract $contract): void
    {
        $rows = PaymentScheduleBuilder::build(
            $contract->start_date,
            $contract->end_date,
            (string) $contract->total_value,
            $contract->payment_cycle
        );

        $this->payments->replaceSchedule($contract, $rows);
    }

    private function generateUniqueCode(): string
    {
        for ($i = 0; $i < 15; $i++) {
            $code = 'CNT-'.now()->format('Y').'-'.strtoupper(Str::random(6));
            if (! Contract::query()->where('code', $code)->exists()) {
                return $code;
            }
        }

        throw new \RuntimeException('Unable to generate a unique contract code.');
    }
}
