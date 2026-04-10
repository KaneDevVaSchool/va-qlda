<?php

namespace App\Policies;

use App\Models\Block;
use App\Models\User;
use App\Services\UserRbacService;

class BlockPolicy
{
    public function __construct(
        private UserRbacService $rbac
    ) {}

    public function create(User $user): bool
    {
        return $this->rbac->can($user, 'vendors.update');
    }

    public function update(User $user, Block $block): bool
    {
        return $this->rbac->can($user, 'vendors.update');
    }
}
