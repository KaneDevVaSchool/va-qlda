<?php

namespace App\Policies;

use App\Enums\ContractApprovalStatus;
use App\Enums\ContractStatus;
use App\Models\Contract;
use App\Models\User;
use App\Services\UserRbacService;

class ContractPolicy
{
    public function __construct(
        private UserRbacService $rbac
    ) {}

    public function viewAny(User $user): bool
    {
        return $this->rbac->can($user, 'contracts.view');
    }

    public function view(User $user, Contract $contract): bool
    {
        return $this->rbac->can($user, 'contracts.view');
    }

    public function create(User $user): bool
    {
        return $this->rbac->can($user, 'contracts.create');
    }

    public function update(User $user, Contract $contract): bool
    {
        if ($contract->created_by === $user->id) {
            return true;
        }

        return $this->rbac->can($user, 'contracts.update');
    }

    public function delete(User $user, Contract $contract): bool
    {
        if ($this->rbac->isPermissionAdmin($user)) {
            return $this->rbac->can($user, 'contracts.view');
        }

        if ($contract->status !== ContractStatus::Draft) {
            return false;
        }

        return $this->rbac->can($user, 'contracts.update') || $contract->created_by === $user->id;
    }

    /** Admin: view soft-deleted contracts (trash). */
    public function viewTrash(User $user): bool
    {
        return $this->rbac->isPermissionAdmin($user);
    }

    public function restore(User $user, Contract $contract): bool
    {
        return $this->rbac->isPermissionAdmin($user);
    }

    public function forceDelete(User $user, Contract $contract): bool
    {
        return $this->rbac->isPermissionAdmin($user);
    }

    public function submit(User $user, Contract $contract): bool
    {
        return $contract->created_by === $user->id || $this->rbac->can($user, 'contracts.update');
    }

    public function approve(User $user, Contract $contract): bool
    {
        $approval = $contract->approvals()
            ->where('status', ContractApprovalStatus::Pending)
            ->orderBy('step')
            ->first();

        return $approval && $approval->approver_id === $user->id;
    }

    public function reject(User $user, Contract $contract): bool
    {
        return $this->approve($user, $contract);
    }

    public function terminate(User $user, Contract $contract): bool
    {
        return $this->rbac->can($user, 'contracts.update');
    }

    public function uploadFiles(User $user, Contract $contract): bool
    {
        if ($contract->status !== ContractStatus::Draft) {
            return false;
        }

        return $this->update($user, $contract);
    }

    public function markPayment(User $user, Contract $contract): bool
    {
        return $this->rbac->can($user, 'contracts.update');
    }
}
