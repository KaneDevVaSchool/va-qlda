<?php

namespace App\Policies;

use App\Models\Department;
use App\Models\User;
use App\Services\UserRbacService;

class DepartmentPolicy
{
    public function __construct(
        private UserRbacService $rbac
    ) {}

    public function create(User $user): bool
    {
        return $this->rbac->can($user, 'vendors.update');
    }

    public function update(User $user, Department $department): bool
    {
        return $this->rbac->can($user, 'vendors.update');
    }
}
