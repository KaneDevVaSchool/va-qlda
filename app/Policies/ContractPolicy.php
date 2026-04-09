<?php

namespace App\Policies;

use App\Enums\ContractApprovalStatus;
use App\Enums\ContractStatus;
use App\Models\Contract;
use App\Models\User;

class ContractPolicy
{
    private function canBrowse(User $user): bool
    {
        return in_array($user->role, ['admin', 'pm', 'tl', 'hr', 'developer'], true);
    }

    private function canManage(User $user): bool
    {
        return in_array($user->role, ['admin', 'pm', 'tl'], true);
    }

    public function viewAny(User $user): bool
    {
        return $this->canBrowse($user);
    }

    public function view(User $user, Contract $contract): bool
    {
        return $this->canBrowse($user);
    }

    public function create(User $user): bool
    {
        return $this->canBrowse($user);
    }

    public function update(User $user, Contract $contract): bool
    {
        if (! $this->canManage($user) && $contract->created_by !== $user->id) {
            return false;
        }

        return true;
    }

    public function delete(User $user, Contract $contract): bool
    {
        return $this->canManage($user) || $contract->created_by === $user->id;
    }

    /** Admin: view soft-deleted contracts (trash). */
    public function viewTrash(User $user): bool
    {
        return $user->role === 'admin';
    }

    public function restore(User $user, Contract $contract): bool
    {
        return $user->role === 'admin';
    }

    public function forceDelete(User $user, Contract $contract): bool
    {
        return $user->role === 'admin';
    }

    public function submit(User $user, Contract $contract): bool
    {
        return $contract->created_by === $user->id || $this->canManage($user);
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
        return $this->canManage($user);
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
        return $this->canManage($user);
    }
}
