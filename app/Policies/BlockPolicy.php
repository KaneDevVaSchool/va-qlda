<?php

namespace App\Policies;

use App\Models\Block;
use App\Models\User;

class BlockPolicy
{
    private function canBrowseContracts(User $user): bool
    {
        return in_array($user->role, ['admin', 'pm', 'tl', 'hr', 'developer'], true);
    }

    public function create(User $user): bool
    {
        return $this->canBrowseContracts($user);
    }

    public function update(User $user, Block $block): bool
    {
        return $this->canBrowseContracts($user);
    }
}
