<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Vendor;
use App\Services\UserRbacService;

class VendorPolicy
{
    public function __construct(
        private UserRbacService $rbac
    ) {}

    public function viewAny(User $user): bool
    {
        return $this->rbac->can($user, 'vendors.view');
    }

    public function view(User $user, Vendor $vendor): bool
    {
        return $this->rbac->can($user, 'vendors.view');
    }

    public function create(User $user): bool
    {
        return $this->rbac->can($user, 'vendors.create');
    }

    public function update(User $user, Vendor $vendor): bool
    {
        return $this->rbac->can($user, 'vendors.update');
    }

    public function delete(User $user, Vendor $vendor): bool
    {
        return $this->rbac->can($user, 'vendors.delete');
    }
}
