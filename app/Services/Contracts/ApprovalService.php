<?php

namespace App\Services\Contracts;

use App\Enums\ContractApprovalStatus;
use App\Enums\ContractStatus;
use App\Events\ContractApproved;
use App\Models\Contract;
use App\Models\ContractApproval;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ApprovalService
{
    public function __construct(
        protected ContractAuditService $audit
    ) {}

    /**
     * @param  list<int>  $orderedApproverUserIds
     */
    public function submit(Contract $contract, array $orderedApproverUserIds, User $actor): void
    {
        $this->assertDraft($contract);

        if ($orderedApproverUserIds === []) {
            abort(422, 'At least one approver is required.');
        }

        DB::transaction(function () use ($contract, $orderedApproverUserIds, $actor) {
            $previousStatus = $contract->only(['status']);
            $contract->approvals()->delete();

            $step = 1;
            foreach ($orderedApproverUserIds as $userId) {
                $contract->approvals()->create([
                    'approver_id' => $userId,
                    'step' => $step,
                    'status' => $step === 1
                        ? ContractApprovalStatus::Pending
                        : ContractApprovalStatus::Queued,
                ]);
                $step++;
            }
            $contract->update(['status' => ContractStatus::PendingApproval]);

            $this->audit->log(
                $contract,
                'contract.submitted',
                $previousStatus,
                $contract->only(['status']),
                $actor->id
            );
        });
    }

    public function approve(Contract $contract, User $actor): void
    {
        $this->assertPendingApproval($contract);
        $this->assertActorIsCurrentApprover($contract, $actor);

        DB::transaction(function () use ($contract, $actor) {
            $current = $this->currentPendingStep($contract);
            abort_if(! $current, 409, 'Approval step is no longer available.');

            $current->update([
                'status' => ContractApprovalStatus::Approved,
                'approved_at' => now(),
            ]);

            $this->audit->log(
                $contract,
                'approval.approved',
                null,
                ['step' => $current->step, 'approver_id' => $actor->id],
                $actor->id
            );

            $nextQueued = $contract->approvals()
                ->where('status', ContractApprovalStatus::Queued)
                ->orderBy('step')
                ->first();

            if ($nextQueued) {
                $nextQueued->update(['status' => ContractApprovalStatus::Pending]);

                return;
            }

            $previous = $contract->only(['status', 'approved_by']);
            $contract->update([
                'status' => ContractStatus::Active,
                'approved_by' => $actor->id,
            ]);

            $this->audit->log(
                $contract,
                'contract.activated',
                $previous,
                $contract->only(['status', 'approved_by']),
                $actor->id
            );

            ContractApproved::dispatch($contract->fresh());
        });
    }

    public function reject(Contract $contract, User $actor): void
    {
        $this->assertPendingApproval($contract);
        $this->assertActorIsCurrentApprover($contract, $actor);

        DB::transaction(function () use ($contract, $actor) {
            $contract->approvals()
                ->whereIn('status', [
                    ContractApprovalStatus::Pending->value,
                    ContractApprovalStatus::Queued->value,
                ])
                ->update(['status' => ContractApprovalStatus::Rejected->value]);

            $previous = $contract->only(['status']);
            $contract->update(['status' => ContractStatus::Draft]);

            $this->audit->log(
                $contract,
                'approval.rejected',
                $previous,
                $contract->only(['status']),
                $actor->id
            );
        });
    }

    private function assertDraft(Contract $contract): void
    {
        if ($contract->status !== ContractStatus::Draft) {
            abort(422, 'Only draft contracts can be submitted for approval.');
        }
    }

    private function assertPendingApproval(Contract $contract): void
    {
        if ($contract->status !== ContractStatus::PendingApproval) {
            abort(422, 'Contract is not awaiting approval.');
        }
    }

    private function assertActorIsCurrentApprover(Contract $contract, User $actor): void
    {
        $current = $this->currentPendingStep($contract);
        if (! $current || $current->approver_id !== $actor->id) {
            abort(403, 'You are not the approver for the current step.');
        }
    }

    private function currentPendingStep(Contract $contract): ?ContractApproval
    {
        return $contract->approvals()
            ->where('status', ContractApprovalStatus::Pending)
            ->orderBy('step')
            ->first();
    }
}
