<?php

namespace App\Policies;

use App\Models\Department;
use App\Models\User;

class DepartmentPolicy
{
    private function canBrowseContracts(User $user): bool
    {
        return in_array($user->role, ['admin', 'pm', 'tl', 'hr', 'developer'], true);
    }

    public function create(User $user): bool
    {
        return $this->canBrowseContracts($user);
    }

    public function update(User $user, Department $department): bool
    {
        return $this->canBrowseContracts($user);
    }
}
